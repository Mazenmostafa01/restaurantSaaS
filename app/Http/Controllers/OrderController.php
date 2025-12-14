<?php

namespace App\Http\Controllers;

use App\Enums\ItemCategoryEnum;
use App\Enums\OrderTypeEnum;
use App\Http\Requests\OrderCreateRequest;
use App\Models\Item;
use App\Models\Order;
use App\Traits\Calculation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use Calculation;

    public function index()
    {
        $orders = Order::get();

        return view('admin.orders.index', compact('orders'));
    }

    public function create()
    {
        $items = Item::withoutTrashed()->get();
        $categories = ItemCategoryEnum::cases();
        $orderType = OrderTypeEnum::cases();

        return view('admin.orders.create', compact('items', 'categories', 'orderType'));
    }

    public function store(OrderCreateRequest $request)
    {
        $request = $request->validated();
        $subTotal = 0;
        $orderDetails = [];
        DB::beginTransaction();
        try {
            foreach ($request['items'] as $itemId => $itemValues) {
                $item = Item::findOrFail($itemId);
                $subTotal += $this->subTotal($item->price, $itemValues['quantity']);
                $orderDetails[$itemId] = [
                    'quantity' => $itemValues['quantity'],
                ];
            }
            $tax = $this->tax($subTotal);
            $netTotal = $subTotal + $tax;

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'price' => $subTotal,
                'tax' => $tax,
                'net' => $netTotal,
                'type' => $request['type'],
                'note' => $request['note'] ?? null,
                'user_id' => auth()->user()->id,
                'customer_id' => $request['customer_id'] ?? null,
            ]);

            foreach ($orderDetails as $itemId => $detail) {
                $order->items()->attach($itemId, ['quantity' => $detail['quantity']]);
            }

            DB::commit();

            return redirect()->route('orders.create');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('order create error', [$e]);

            return back()->with('error', 'Failed to create order.');
        }
    }

    public function show() {}

    public function edit() {}

    public function update() {}

    public function delete(Order $order)
    {
        DB::beginTransaction();
        try {
            $order->items()->detach();
            $order->delete();
            DB::commit();
            return redirect()->route('orders.index');
        } catch (\Exception $e) {
            Log::info('delete order error', [$e]);

            return back()->with('error', 'Failed to delete order.');
        }
    }

    private function generateOrderNumber()
    {
        return strtoupper(uniqid());
    }
}
