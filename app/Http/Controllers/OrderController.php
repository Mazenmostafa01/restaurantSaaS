<?php

namespace App\Http\Controllers;

use App\DTOs\OrderWriteData;
use App\Enums\ItemCategoryEnum;
use App\Enums\OrderTypeEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(): View
    {
        $orders = Order::orderBy('created_at', 'desc')->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function create(): View
    {
        $items = Item::orderBy('category')->get();
        $categories = ItemCategoryEnum::cases();
        $orderType = OrderTypeEnum::cases();
        $customers = Customer::select('id', 'name')->get();

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
        $customers = Customer::select('id', 'name')->get();
        $orderType = OrderTypeEnum::cases();
        $items = Item::orderBy('category')->get();

        return view('admin.orders.edit', compact('order', 'customers', 'orderType', 'items'));
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
}
