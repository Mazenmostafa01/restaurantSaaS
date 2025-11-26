<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function create()
    {
        return view('items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'unique:items,name', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', 'in:food,beverage'],
            'description' => ['nullable', 'string'],
        ]);

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

            return back()->withErrors(['error' => 'Failed to create item.']);
        }
    }

    public function show(Item $item)
    {
        return view('items.show', compact('item'));
    }

    public function edit() {}

    public function update() {}

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
            return back()->withErrors(['error' => 'Failed to delete item.']);
        }
    }
}
