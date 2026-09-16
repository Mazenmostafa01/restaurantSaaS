<?php

namespace App\Http\Controllers;

use App\Enums\ItemCategoryEnum;
use App\Events\ItemUpdateEvent;
use App\Http\Requests\AddItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(): View
    {
        $items = Item::withTrashed()->with('attachments')->paginate(15);

        return view('admin.items.index', compact('items'));
    }

    public function create(): View
    {
        $categories = ItemCategoryEnum::cases();

        return view('admin.items.create', compact('categories'));
    }

    public function store(AddItemRequest $request): RedirectResponse
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
            Log::error('Failed to create item', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to create item.');
        }
    }

    public function show(Item $item): View
    {
        $attachments = $item->attachments->map->url();

        return view('admin.items.show', compact('item', 'attachments'));
    }

    public function edit(Item $item): View
    {
        $attachments = $item->attachments->map->url();
        $categories = ItemCategoryEnum::cases();

        return view('admin.items.edit', compact('item', 'attachments', 'categories'));
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $item->update([
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
            Log::error('item update error', ['error' => $e->getMessage()]);
            DB::rollBack();

            return back()->with('error', 'failed to update item.');
        }
    }

    public function delete(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item removed.');
    }

    public function restore(int|string $item): RedirectResponse
    {
        $trashedItem = Item::onlyTrashed()->findOrFail($item);
        $trashedItem->restore();

        return redirect()->route('items.index')->with('success', 'item restored.');
    }
}
