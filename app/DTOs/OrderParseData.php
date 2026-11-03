<?php

namespace App\DTOs;

use Illuminate\Http\Request;
use InvalidArgumentException;

readonly class OrderParseData
{
    public function __construct(
        public ?string $text = null,
        public ?AudioPayload $audio = null,
        public ?string $preferredLanguage = null,
        public string $channel = 'customer_web',
    ) {
        if (blank($this->text) && $this->audio === null) {
            throw new InvalidArgumentException('Either text or audio must be provided for order parsing.');
        }
    }

    public static function fromCustomerRequest(Request $request): self
    {
        $audioFile = $request->file('audio');

        return new self(
            text: $request->input('text'),
            audio: $audioFile ? AudioPayload::fromFile($audioFile) : null,
            preferredLanguage: $request->input('preferred_language'),
            channel: 'customer_web',
        );
    }

    /**
     * Factory for external channels (e.g. WhatsApp group orders).
     */
    public static function fromWhatsApp(
        ?string $text = null,
        ?string $audioBase64 = null,
        ?string $audioMimeType = 'audio/ogg',
        ?int $audioDurationSeconds = null,
        ?string $preferredLanguage = null,
    ): self {
        $audio = ! blank($audioBase64)
            ? AudioPayload::fromBase64($audioBase64, $audioMimeType, $audioDurationSeconds)
            : null;

        return new self(
            text: $text,
            audio: $audio,
            preferredLanguage: $preferredLanguage,
            channel: 'whatsapp_group',
        );
    }

    public function hasAudio(): bool
    {
        return $this->audio !== null;
    }
}
