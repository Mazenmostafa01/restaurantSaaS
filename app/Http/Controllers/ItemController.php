<?php

namespace App\Http\Controllers;

use App\DTOs\ItemWriteData;
use App\Enums\ItemCategoryEnum;
use App\Http\Requests\AddItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Services\ItemService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    public function __construct(private readonly ItemService $itemService) {}

    public function index(Request $request): View
    {
        $query = Item::withTrashed()->with('attachments');

        if ($term = trim((string) $request->input('q', ''))) {
            $query->where('name', 'like', "{$term}%");
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $items = $query->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();
        $categories = ItemCategoryEnum::cases();

        return view('admin.items.index', compact('items', 'categories'));
    }

    public function create(): View
    {
        $categories = ItemCategoryEnum::cases();

        return view('admin.items.create', compact('categories'));
    }

    public function store(AddItemRequest $request): RedirectResponse
    {
        try {
            $this->itemService->createItem(ItemWriteData::fromAddRequest($request));

            return redirect()->route('items.create')->with('success', 'Item created successfully!');
        } catch (Exception $exception) {
            Log::error('Failed to create item', ['exception' => $exception]);

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
        try {
            $this->itemService->updateItem($item, ItemWriteData::fromUpdateRequest($request));

            return redirect()->route('items.index')->with('success', 'item updated successfully.');
        } catch (Exception $exception) {
            Log::error('item update error', ['exception' => $exception]);

            return back()->with('error', 'failed to update item.');
        }
    }

    public function delete(Item $item): RedirectResponse
    {
        $this->itemService->deleteItem($item);

        return redirect()->route('items.index')->with('success', 'Item removed.');
    }

    public function restore(int|string $item): RedirectResponse
    {
        $this->itemService->restoreItem($item);

        return redirect()->route('items.index')->with('success', 'item restored.');
    }
}
