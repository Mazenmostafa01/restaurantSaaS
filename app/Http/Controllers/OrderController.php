<?php

namespace App\Http\Controllers;

use App\DTOs\OrderWriteData;
use App\Enums\ItemCategoryEnum;
use App\Enums\OrderTypeEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\QuickCustomerRequest;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): View
    {
        $query = Order::query()->with(['customer', 'user']);
        $this->applyOrderFilters($query, $request);
        $orders = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $phone = trim((string) $request->input('q', ''));

        if ($phone === '') {
            $customers = Customer::select('id', 'name', 'phone_number', 'address')->latest('id')->limit(15)->get();

            return response()->json(['customers' => $customers]);
        }

        $customers = Customer::select('id', 'name', 'phone_number', 'address')
            ->where('phone_number', 'like', "{$phone}%")
            ->limit(15)
            ->get();

        return response()->json(['customers' => $customers]);
    }

    public function quickStoreCustomer(QuickCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::create([
            'name' => trim($validated['name']),
            'phone_number' => trim($validated['phone_number']),
            'address' => isset($validated['address']) && trim($validated['address']) !== '' ? trim($validated['address']) : null,
            'email' => null,
            'password' => null,
        ]);

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone_number' => $customer->phone_number,
                'address' => $customer->address,
            ],
        ], 201);
    }

    public function create(): View
    {
        $items = Item::orderBy('category')->get();
        $categories = ItemCategoryEnum::cases();
        $orderType = OrderTypeEnum::cases();
        $customers = Customer::select('id', 'name', 'phone_number', 'address')->latest('id')->limit(50)->get();

        return view('admin.orders.create', compact('items', 'categories', 'orderType', 'customers'));
    }

    public function store(OrderCreateRequest $request): RedirectResponse
    {
        try {
            $this->orderService->createOrder(OrderWriteData::fromAdminRequest($request));

            return redirect()->route('orders.create');
        } catch (Exception $exception) {
            Log::error('order create error', ['exception' => $exception]);

            return back()->with('error', 'Failed to create order.');
        }
    }

    public function edit(Order $order): View
    {
        $order->loadMissing(['customer', 'items']);
        $categories = ItemCategoryEnum::cases();
        $orderType = OrderTypeEnum::cases();
        $items = Item::orderBy('category')->get();

        foreach ($order->items as $orderItem) {
            if (! $items->contains('id', $orderItem->id)) {
                $items->push($orderItem);
            }
        }

        $customers = Customer::select('id', 'name', 'phone_number', 'address')->latest('id')->limit(50)->get();

        if ($order->customer && ! $customers->contains('id', $order->customer_id)) {
            $customers->prepend($order->customer);
        }

        return view('admin.orders.edit', compact('order', 'customers', 'orderType', 'items', 'categories'));
    }

    public function update(OrderUpdateRequest $request, Order $order): RedirectResponse
    {
        try {
            $this->orderService->updateOrder($order, OrderWriteData::fromAdminRequest($request));

            return redirect()->route('orders.index');
        } catch (Exception $exception) {
            Log::error('order update error', ['exception' => $exception]);

            return back()->with('error', 'Failed to update order.');
        }
    }

    public function delete(Order $order): RedirectResponse
    {
        try {
            $this->orderService->deleteOrder($order);

            return redirect()->route('orders.index');
        } catch (Exception $exception) {
            Log::error('delete order error', ['exception' => $exception]);

            return back()->with('error', 'Failed to delete order.');
        }
    }

    private function applyOrderFilters(Builder $query, Request $request): void
    {
        if ($searchTerm = trim((string) $request->input('q', ''))) {
            $cleanSearch = ltrim($searchTerm, '#');
            $query->where(function (Builder $q) use ($cleanSearch, $searchTerm): void {
                $q->where('order_number', 'like', "{$cleanSearch}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($searchTerm): void {
                        $customerQuery->where('phone_number', 'like', "{$searchTerm}%");
                    });
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
    }
}
