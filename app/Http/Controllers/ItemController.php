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
    public function index()
    {
        $items = Item::withTrashed()->with('attachments')->get();

        return view('admin.items.index', compact('items'));
    }

    public function create()
    {
        $categories = ItemCategoryEnum::cases();

        return view('admin.items.create', compact('categories'));
    }

    public function store(AddItemRequest $request)
    {

        DB::beginTransaction();
        try {
            $item = Item::create([
                'name' => $request->name,
                'price' => $request->price,
                'category' => $request->category,
                'description' => $request->description ?? null,
            ]);

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

        return view('admin.items.show', compact('item', 'attachments'));
    }

    public function edit(Item $item)
    {
        $attachments = $item->attachments->map->url();
        $categories = ItemCategoryEnum::cases();

        return view('admin.items.edit', compact('item', 'attachments', 'categories'));
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
                $attachments = $item->attachments()->whereIn('id', $request->delete_images)->get();
                foreach ($attachments as $attachment) {
                    Storage::disk($attachment->disk)->delete($attachment->path);
                    $attachment->delete();
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
            DB::afterCommit(function () use ($item) {
                event(new ItemUpdateEvent($item));
            });

            return redirect()->route('items.index')->with('success', 'item updated successfully.');
        } catch (\Exception $e) {
            Log::info('item update error', [$e]);
            DB::rollBack();

            return back()->with('error', 'failed to update item.');
        }
    }

    public function delete(Item $item)
    {
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item removed.');
    }

    public function restore($item)
    {
        $trashedItem = Item::withTrashed()->find($item);
        if ($trashedItem) {
            $trashedItem->restore();

            return redirect()->route('items.index')->with('success', 'item restored.');
        }

        return back()->with('error', 'Failed to restore item.');
    }
}
