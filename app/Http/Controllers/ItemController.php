<?php

namespace App\Http\Controllers;

use App\Enums\ItemCategoryEnum;
use App\Events\ItemUpdateEvent;
use App\Http\Requests\AddItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $position => $image) {
                    $path = Storage::disk('public')->put('/images', $image);

                    $item->attachments()->create([
                        'path' => $path,
                        'filename' => str_replace('images/', '', $path),
                        'disk' => 'public',
                        'mime_type' => $image->getMimeType(),
                        'filesize' => $image->getSize(),
                        'position' => $position,
                        'is_primary' => $position === 0,
                    ]);
                }
            }
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
        $attachments = $item->attachments->map->url();

        return view('items.show', compact('item', 'attachments'));
    }

    public function edit(Item $item)
    {
        $attachments = $item->attachments->map->url();

        return view('items.edit', compact('item', 'attachments'));
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

            if ($request->filled('delete_images')) {
                foreach ($request->delete_images as $id) {
                    $attachment = $item->attachments()->find($id);
                    if ($attachment) {
                        Storage::disk($attachment->disk)->delete($attachment->path);
                        $attachment->delete();
                    }
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $position => $image) {
                    $path = Storage::disk('public')->put('/images', $image);

                    $item->attachments()->create([
                        'path' => $path,
                        'filename' => str_replace('images/', '', $path),
                        'disk' => 'public',
                        'mime_type' => $image->getMimeType(),
                        'filesize' => $image->getSize(),
                    ]);
                }
            }
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
