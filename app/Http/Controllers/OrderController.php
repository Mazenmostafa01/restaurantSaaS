<?php

namespace App\Http\Controllers;

use App\Enums\ItemCategoryEnum;
use App\Enums\OrderTypeEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Traits\Calculation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use Calculation;

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
        $validated = $request->validated();
        $subTotal = 0;
        $orderDetails = [];
        $items = Item::whereIn('id', array_keys($validated['items']))->get()->keyBy('id');

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemId => $selectedItem) {
                if (! isset($items[$itemId])) {
                    continue;
                }
                $item = $items[$itemId];
                $subTotal += $this->subTotal($item->price, $selectedItem['quantity']);
                $orderDetails[$itemId] = [
                    'quantity' => $selectedItem['quantity'],
                ];
            }
            $tax = $this->tax($subTotal);
            $netTotal = $subTotal + $tax;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'price' => $subTotal,
                'tax' => $tax,
                'net' => $netTotal,
                'type' => $validated['type'],
                'note' => $validated['note'] ?? null,
                'user_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
            ]);

            $order->items()->attach($orderDetails);

            DB::commit();

            return redirect()->route('orders.create');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('order create error', ['error' => $e->getMessage()]);

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
        $validated = $request->validated();
        $subTotal = 0;
        $orderDetails = [];
        $items = Item::whereIn('id', array_keys($validated['items']))->get()->keyBy('id');

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemId => $selectedItem) {
                if (! isset($items[$itemId])) {
                    continue;
                }
                $item = $items[$itemId];
                $subTotal += $this->subTotal($item->price, $selectedItem['quantity']);
                $orderDetails[$itemId] = [
                    'quantity' => $selectedItem['quantity'],
                ];
            }
            $tax = $this->tax($subTotal);
            $netTotal = $subTotal + $tax;

            $order->update([
                'price' => $subTotal,
                'tax' => $tax,
                'net' => $netTotal,
                'type' => $validated['type'],
                'note' => $validated['note'] ?? null,
                'user_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
            ]);

            $order->items()->sync($orderDetails);

            DB::commit();

            return redirect()->route('orders.index');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('order update error', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to update order.');
        }
    }

    public function delete(Order $order): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $order->items()->detach();
            $order->delete();
            DB::commit();

            return redirect()->route('orders.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('delete order error', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to delete order.');
        }
    }

    private function generateOrderNumber(): string
    {
        return strtoupper(uniqid());
    }
}
