<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\TenantContext;
use App\Traits\Calculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CustomerOrderController extends Controller
{
    use Calculation;

    /**
     * List the authenticated customer's orders for this restaurant.
     */
    public function index(): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::where('customer_id', $customer->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'orders' => $orders->through(fn ($order) => [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'price'        => number_format($order->price, 2),
                'tax'          => number_format($order->tax, 2),
                'net'          => number_format($order->net, 2),
                'type'         => $order->type,
                'note'         => $order->note,
                'items_count'  => $order->items->count(),
                'created_at'   => $order->created_at->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Show a single order with full item details.
     */
    public function show(Restaurant $restaurant, Order $order): JsonResponse
    {
        $customer = Auth::guard('customer')->user();

        // Ensure this order belongs to the authenticated customer
        if ($order->customer_id !== $customer->id) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->load('items');

        return response()->json([
            'order' => [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'price'        => number_format($order->price, 2),
                'tax'          => number_format($order->tax, 2),
                'net'          => number_format($order->net, 2),
                'type'         => $order->type,
                'note'         => $order->note,
                'created_at'   => $order->created_at->toDateTimeString(),
                'items'        => $order->items->map(fn ($item) => [
                    'id'       => $item->id,
                    'name'     => $item->name,
                    'price'    => number_format($item->price, 2),
                    'quantity' => $item->pivot->quantity,
                ]),
            ],
        ]);
    }

    /**
     * Place a new order as a customer.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->id();

        $validated = $request->validate([
            'type'               => ['required', 'in:take_away,delivery'],
            'note'               => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.id'         => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where('restaurant_id', $tenantId),
            ],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $customer = Auth::guard('customer')->user();
        $subTotal = 0;
        $orderDetails = [];

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                $item = Item::findOrFail($itemData['id']);
                $subTotal += $this->subTotal($item->price, $itemData['quantity']);
                $orderDetails[$item->id] = ['quantity' => $itemData['quantity']];
            }

            $tax = $this->tax($subTotal);
            $netTotal = $subTotal + $tax;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'price'        => $subTotal,
                'tax'          => $tax,
                'net'          => $netTotal,
                'type'         => $validated['type'],
                'note'         => $validated['note'] ?? null,
                'user_id'      => null, // Customer orders have no admin user
                'customer_id'  => $customer->id,
            ]);

            $order->items()->attach($orderDetails);

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully.',
                'order'   => [
                    'id'           => $order->id,
                    'order_number' => $order->order_number,
                    'net'          => number_format($netTotal, 2),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer order creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to place order. Please try again.',
            ], 500);
        }
    }

    private function generateOrderNumber(): string
    {
        return 'C-' . strtoupper(uniqid());
    }
}
