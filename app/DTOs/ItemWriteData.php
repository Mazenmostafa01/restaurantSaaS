<?php

namespace App\DTOs;

use App\Http\Requests\AddItemRequest;
use App\Http\Requests\UpdateItemRequest;
use Illuminate\Http\UploadedFile;

readonly class ItemWriteData
{
    /**
     * @param  array<int, UploadedFile>  $uploadedImages
     * @param  array<int, int>  $attachmentIdsToDelete
     */
    public function __construct(
        public string $name,
        public float|string $price,
        public string $category,
        public ?string $description = null,
        public array $uploadedImages = [],
        public array $attachmentIdsToDelete = [],
    ) {}

    public static function fromAddRequest(AddItemRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            price: $request->validated('price'),
            category: $request->validated('category'),
            description: $request->validated('description'),
            uploadedImages: $request->file('images') ?? [],
        );
    }

    public static function fromUpdateRequest(UpdateItemRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            price: $request->validated('price'),
            category: $request->validated('category'),
            description: $request->validated('description'),
            uploadedImages: $request->file('images') ?? [],
            attachmentIdsToDelete: $request->validated('delete_images') ?? [],
        );
    }
}
