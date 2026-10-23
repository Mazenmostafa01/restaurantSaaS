<?php

namespace App\Services;

use App\DTOs\ItemWriteData;
use App\Events\ItemUpdateEvent;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ItemService
{
    public function __construct(private readonly TenantCacheService $tenantCache) {}

    public function createItem(ItemWriteData $itemData): Item
    {
        $storedUploads = $this->writeFilesToDisk($itemData->uploadedImages);

        try {
            return DB::transaction(function () use ($itemData, $storedUploads): Item {
                $item = Item::create([
                    'name' => $itemData->name,
                    'price' => $itemData->price,
                    'category' => $itemData->category,
                    'description' => $itemData->description,
                ]);

                $this->attachStoredFiles($item, $storedUploads, hasExistingAttachments: false);

                DB::afterCommit(function () use ($item): void {
                    $this->tenantCache->forgetMenu((int) $item->restaurant_id);
                });

                return $item;
            });
        } catch (Throwable $exception) {
            $this->cleanupOrphanedFiles($storedUploads);
            throw $exception;
        }
    }

    public function updateItem(Item $item, ItemWriteData $itemData): Item
    {
        $storedUploads = $this->writeFilesToDisk($itemData->uploadedImages);

        try {
            return DB::transaction(function () use ($item, $itemData, $storedUploads): Item {
                $item->update([
                    'name' => $itemData->name,
                    'price' => $itemData->price,
                    'category' => $itemData->category,
                    'description' => $itemData->description,
                ]);

                $this->deleteAttachments($item, $itemData->attachmentIdsToDelete);
                $hasExisting = $item->attachments()->exists();
                $this->attachStoredFiles($item, $storedUploads, $hasExisting);

                DB::afterCommit(function () use ($item): void {
                    $this->tenantCache->forgetMenu((int) $item->restaurant_id);
                    event(new ItemUpdateEvent($item));
                });

                return $item;
            });
        } catch (Throwable $exception) {
            $this->cleanupOrphanedFiles($storedUploads);
            throw $exception;
        }
    }

    public function restoreItem(int|string $itemId): Item
    {
        $trashedItem = Item::onlyTrashed()->findOrFail($itemId);
        $trashedItem->restore();
        $this->tenantCache->forgetMenu((int) $trashedItem->restaurant_id);

        return $trashedItem;
    }

    public function deleteItem(Item $item): void
    {
        $tenantId = (int) $item->restaurant_id;
        $item->delete();
        $this->tenantCache->forgetMenu($tenantId);
    }

    private function writeFilesToDisk(array $images): array
    {
        $stored = [];
        foreach ($images as $position => $image) {
            $path = Storage::disk('public')->put('/images', $image);
            $stored[] = [
                'path' => $path,
                'filename' => str_replace('images/', '', $path),
                'disk' => 'public',
                'mime_type' => $image->getMimeType(),
                'filesize' => $image->getSize(),
                'position' => $position,
            ];
        }

        return $stored;
    }

    private function attachStoredFiles(Item $item, array $storedUploads, bool $hasExistingAttachments): void
    {
        foreach ($storedUploads as $index => $upload) {
            $item->attachments()->create([
                ...$upload,
                'is_primary' => ! $hasExistingAttachments && $index === 0,
            ]);
        }
    }

    private function deleteAttachments(Item $item, array $attachmentIds): void
    {
        if ($attachmentIds === []) {
            return;
        }

        $attachments = $item->attachments()->whereIn('id', $attachmentIds)->get();
        $filesToDelete = $attachments->map(static fn ($attachment): array => [
            'disk' => $attachment->disk,
            'path' => $attachment->path,
        ])->all();

        $item->attachments()->whereIn('id', $attachmentIds)->delete();

        DB::afterCommit(static function () use ($filesToDelete): void {
            foreach ($filesToDelete as $file) {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        });
    }

    private function cleanupOrphanedFiles(array $storedUploads): void
    {
        foreach ($storedUploads as $upload) {
            Storage::disk($upload['disk'])->delete($upload['path']);
        }
    }
}
