<?php

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\OrderWriteData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerOrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderSummaryResource;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerOrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * List the authenticated customer's orders for this restaurant.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->withCount('items')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'orders' => $orders->through(
                static fn (Order $order): array => (new OrderSummaryResource($order))->resolve($request)
            ),
        ]);
    }

    /**
     * Show a single order with full item details.
     */
    public function show(Request $request, Restaurant $restaurant, Order $order): JsonResponse
    {
        if ((int) $order->customer_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->load('items');

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * Place a new order as a customer.
     */
    public function store(CustomerOrderStoreRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(OrderWriteData::fromCustomerRequest($request));

            return response()->json([
                'message' => 'Order placed successfully.',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'net' => number_format((float) $order->net, 2),
                ],
            ], 201);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'One or more selected items are no longer available.',
            ], 422);
        } catch (Exception $exception) {
            Log::error('Customer order creation failed', ['exception' => $exception]);

            return response()->json([
                'message' => 'Failed to place order. Please try again.',
            ], 500);
        }
    }
}
