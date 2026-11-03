<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

readonly class AudioPayload
{
    public function __construct(
        public ?UploadedFile $file = null,
        public ?string $base64 = null,
        public ?string $mimeType = null,
        public ?int $durationSeconds = null,
    ) {
        if ($this->file === null && blank($this->base64)) {
            throw new InvalidArgumentException('Audio payload requires either an UploadedFile or a base64 string.');
        }
    }

    public static function fromFile(UploadedFile $file): self
    {
        return new self(
            file: $file,
            mimeType: $file->getMimeType(),
        );
    }

    public static function fromBase64(string $base64, ?string $mimeType = 'audio/ogg', ?int $durationSeconds = null): self
    {
        return new self(
            base64: $base64,
            mimeType: $mimeType,
            durationSeconds: $durationSeconds,
        );
    }
}
