<?php

namespace App\Http\Controllers;

use App\Enums\ItemCategoryEnum;
use App\Events\ItemUpdateEvent;
use App\Http\Requests\AddItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    public function create()
    {
        $categories = ItemCategoryEnum::cases();

        return view('items.create', compact('categories'));
    }

    public function store(AddItemRequest $request)
    {

        DB::beginTransaction();
        try {
            $item = new Item;
            $item->name = $request->name;
            $item->price = $request->price;
            $item->category = $request->category;
            $item->description = $request->description ?? null;
            $item->save();
            DB::commit();

            return redirect()->route('items.create')->with('success', 'Item created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            info('error', [$e]);

            return back()->with('error', 'Failed to create item.');
        }
    }

    public function show(Item $item)
    {
        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('items.edit', compact('item'));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        DB::beginTransaction();
        try {
            $item->update(
                [
                    'name' => $request->name,
                    'price' => $request->price,
                    'category' => $request->category,
                    'description' => $request->description ?? null,
                ]);
            DB::commit();
            event(new ItemUpdateEvent($item));

            return redirect()->route('items.edit', $item->id)->with('success', 'item updated successfully.');
        } catch (\Exception $e) {
            Log::info('item update error', [$e]);
            DB::rollBack();

            return back()->with('error', 'failed to update item.');
        }
    }

    public function delete(Item $item)
    {
        DB::beginTransaction();
        try {
            $item->delete();
            DB::commit();

            return redirect()->route('items.create')->with('success', 'Item deleted');
        } catch (\Exception $e) {
            DB::rollBack();
            info('delete.item.error', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to delete item.');
        }
    }
}
