<?php

namespace App\Services;

use App\Ai\Agents\OrderParserAgent;
use App\DTOs\OrderParseData;
use App\DTOs\ParsedOrderData;
use App\DTOs\ParseMetadata;
use App\DTOs\ParsePricing;
use App\Models\Item;
use App\Traits\Calculation;
use Laravel\Ai\Files\Audio;

class NaturalLanguageOrderParserService
{
    use Calculation;

    public function __construct(
        private readonly TenantCacheService $cacheService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function parse(OrderParseData $orderInput): ParsedOrderData
    {
        $tenant = $this->tenantContext->get();
        $restaurantName = $tenant?->name ?? 'Restaurant';
        $menuDigest = $this->cacheService->getAiMenuDigest();

        if (empty($menuDigest)) {
            return new ParsedOrderData(
                matchedItems: [],
                unmatchedItems: [],
                pricing: new ParsePricing(0.0, 0.0, 0.0),
                metadata: new ParseMetadata(
                    notes: ['Menu is currently empty. Please try again later.'],
                    confidenceScore: 0.0,
                    languageDetected: 'unknown',
                ),
            );
        }

        $attachments = $this->buildAttachments($orderInput);
        $promptText = $this->buildPromptText($orderInput);

        $agent = new OrderParserAgent(
            restaurantName: $restaurantName,
            menuDigest: $menuDigest,
        );

        $response = $agent->prompt(
            $promptText,
            attachments: $attachments,
            provider: config('ai.order_parser.provider'),
            model: config('ai.order_parser.model'),
            timeout: (int) config('ai.order_parser.timeout_seconds', 12),
        );

        $rawMatched = (array) ($response['matched_items'] ?? []);
        $rawUnmatched = (array) ($response['unmatched_items'] ?? []);
        $rawNotes = (array) ($response['notes'] ?? []);
        $rawSplits = (array) ($response['splits'] ?? []);
        $languageDetected = (string) ($response['language_detected'] ?? 'unknown');

        $menuItemsById = $this->cacheService->getMenuItemsById();
        $minConfidence = (float) config('ai.order_parser.min_confidence', 0.65);
        $currentTenantId = $this->tenantContext->id();

        [$validMatched, $demotedUnmatched] = $this->validateAndFilterMatchedItems(
            $rawMatched,
            $menuItemsById,
            $minConfidence,
            $currentTenantId,
        );

        $unmatchedItems = [];
        foreach ($rawUnmatched as $rawUnmatchedItem) {
            $unmatchedItems[] = [
                'raw_text' => (string) ($rawUnmatchedItem['raw_text'] ?? ''),
                'reason' => (string) ($rawUnmatchedItem['reason'] ?? 'not_on_menu'),
                'candidate_matches' => (array) ($rawUnmatchedItem['candidate_matches'] ?? []),
            ];
        }

        foreach ($demotedUnmatched as $demotedItem) {
            $unmatchedItems[] = $demotedItem;
        }

        $splits = $this->buildSplits($validMatched, $rawSplits);
        $matchedItems = $this->deduplicateMatchedItems($validMatched);
        $pricing = $this->calculatePricing($matchedItems);

        $confidenceScore = ! empty($matchedItems)
            ? round(array_sum(array_column($matchedItems, 'confidence')) / count($matchedItems), 2)
            : (! empty($unmatchedItems) ? 0.0 : 1.0);

        $metadata = new ParseMetadata(
            notes: array_values(array_filter(array_map('trim', $rawNotes))),
            splits: $splits,
            confidenceScore: $confidenceScore,
            languageDetected: $languageDetected,
        );

        return new ParsedOrderData(
            matchedItems: $matchedItems,
            unmatchedItems: array_values($unmatchedItems),
            pricing: $pricing,
            metadata: $metadata,
        );
    }

    private function buildAttachments(OrderParseData $orderInput): array
    {
        $attachments = [];

        if ($orderInput->hasAudio()) {
            $audio = $orderInput->audio;
            if ($audio?->file !== null) {
                $attachments[] = Audio::fromPath($audio->file->getRealPath(), $audio->mimeType);
            } elseif (! blank($audio?->base64)) {
                $attachments[] = Audio::fromBase64((string) $audio->base64, $audio->mimeType);
            }
        }

        return $attachments;
    }

    private function buildPromptText(OrderParseData $orderInput): string
    {
        $promptText = ! blank($orderInput->text)
            ? (string) $orderInput->text
            : 'Parse this customer order into structured items.';

        if (! blank($orderInput->preferredLanguage)) {
            $promptText .= " (Customer preferred language: {$orderInput->preferredLanguage})";
        }

        return $promptText;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawMatched
     * @param  array<int, Item>  $menuItemsById
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function validateAndFilterMatchedItems(
        array $rawMatched,
        array $menuItemsById,
        float $minConfidence,
        ?int $currentTenantId,
    ): array {
        $validMatched = [];
        $demotedUnmatched = [];

        foreach ($rawMatched as $rawItem) {
            $id = isset($rawItem['id']) ? (int) $rawItem['id'] : 0;
            $name = (string) ($rawItem['name'] ?? '');
            $quantity = max(1, (int) ($rawItem['quantity'] ?? 1));
            $note = ! empty($rawItem['note']) ? (string) $rawItem['note'] : null;
            $person = ! empty($rawItem['person']) ? (string) $rawItem['person'] : null;
            $confidence = isset($rawItem['confidence']) ? (float) $rawItem['confidence'] : 1.0;

            if (! isset($menuItemsById[$id])) {
                $demotedUnmatched[] = [
                    'raw_text' => $name ?: "Item #{$id}",
                    'reason' => 'not_on_menu',
                    'candidate_matches' => [],
                ];

                continue;
            }

            /** @var Item $dbItem */
            $dbItem = $menuItemsById[$id];

            if ($currentTenantId !== null && (int) $dbItem->restaurant_id !== $currentTenantId) {
                $demotedUnmatched[] = [
                    'raw_text' => $name ?: "Item #{$id}",
                    'reason' => 'not_on_menu',
                    'candidate_matches' => [],
                ];

                continue;
            }

            if ($confidence < $minConfidence) {
                $demotedUnmatched[] = [
                    'raw_text' => $name ?: (string) $dbItem->name,
                    'reason' => 'ambiguous',
                    'candidate_matches' => [(string) $dbItem->name],
                ];

                continue;
            }

            $unitPrice = (float) $dbItem->price;

            $validMatched[] = [
                'id' => $id,
                'name' => (string) $dbItem->name,
                'quantity' => $quantity,
                'note' => $note,
                'person' => $person,
                'confidence' => $confidence,
                'unit_price' => $unitPrice,
                'subtotal' => (float) $this->subTotal($unitPrice, $quantity),
            ];
        }

        return [$validMatched, $demotedUnmatched];
    }

    /**
     * @param  array<int, array<string, mixed>>  $validMatched
     * @param  array<string, mixed>  $rawSplits
     * @return array<string, array{items: array<int, array<string, mixed>>, subtotal: float}>
     */
    private function buildSplits(array $validMatched, array $rawSplits): array
    {
        $splits = [];

        foreach ($validMatched as $matchedItem) {
            if (! empty($matchedItem['person'])) {
                $person = (string) $matchedItem['person'];
                if (! isset($splits[$person])) {
                    $splits[$person] = [
                        'items' => [],
                        'subtotal' => 0.0,
                    ];
                }

                $splits[$person]['items'][] = [
                    'id' => $matchedItem['id'],
                    'name' => $matchedItem['name'],
                    'quantity' => $matchedItem['quantity'],
                    'unit_price' => $matchedItem['unit_price'],
                    'subtotal' => $matchedItem['subtotal'],
                ];
                $splits[$person]['subtotal'] = round($splits[$person]['subtotal'] + $matchedItem['subtotal'], 2);
            }
        }

        if (empty($splits) && ! empty($rawSplits)) {
            $validMatchedById = collect($validMatched)->keyBy('id')->all();

            foreach ($rawSplits as $personName => $personData) {
                if (! is_array($personData)) {
                    continue;
                }

                $personItems = [];
                $personSubtotal = 0.0;

                foreach ((array) ($personData['items'] ?? []) as $pItem) {
                    $pId = (int) ($pItem['id'] ?? 0);
                    if (isset($validMatchedById[$pId])) {
                        $validItem = $validMatchedById[$pId];
                        $pQty = max(1, (int) ($pItem['quantity'] ?? 1));
                        $pUnitPrice = (float) $validItem['unit_price'];
                        $pSub = (float) $this->subTotal($pUnitPrice, $pQty);

                        $personItems[] = [
                            'id' => $pId,
                            'name' => $validItem['name'],
                            'quantity' => $pQty,
                            'unit_price' => $pUnitPrice,
                            'subtotal' => $pSub,
                        ];
                        $personSubtotal += $pSub;
                    }
                }

                if (! empty($personItems)) {
                    $splits[(string) $personName] = [
                        'items' => $personItems,
                        'subtotal' => round($personSubtotal, 2),
                    ];
                }
            }
        }

        return $splits;
    }

    /**
     * @param  array<int, array<string, mixed>>  $validMatched
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateMatchedItems(array $validMatched): array
    {
        $deduped = [];

        foreach ($validMatched as $matchedItem) {
            $id = $matchedItem['id'];

            if (! isset($deduped[$id])) {
                $deduped[$id] = $matchedItem;

                continue;
            }

            $existing = &$deduped[$id];
            $existing['quantity'] += $matchedItem['quantity'];
            $existing['subtotal'] = (float) $this->subTotal($existing['unit_price'], $existing['quantity']);

            $existingNote = $existing['note'];
            $incomingNote = $matchedItem['note'];
            $existingPerson = $existing['person'];
            $incomingPerson = $matchedItem['person'];

            if (! empty($incomingNote)) {
                if (empty($existingNote)) {
                    $existing['note'] = (! empty($incomingPerson) && ! empty($existingPerson) && $incomingPerson !== $existingPerson)
                        ? "{$incomingPerson}: {$incomingNote}"
                        : $incomingNote;
                } elseif (! str_contains($existingNote, $incomingNote)) {
                    if (! empty($existingPerson) && ! empty($incomingPerson) && $existingPerson !== $incomingPerson) {
                        if (! str_starts_with($existingNote, "{$existingPerson}:")) {
                            $existingNote = "{$existingPerson}: {$existingNote}";
                        }
                        $incomingPart = "{$incomingPerson}: {$incomingNote}";
                        $existing['note'] = "{$existingNote}, {$incomingPart}";
                    } else {
                        $existing['note'] = "{$existingNote}, {$incomingNote}";
                    }
                }
            }

            if (! empty($incomingPerson)) {
                if (empty($existing['person'])) {
                    $existing['person'] = $incomingPerson;
                } elseif (! str_contains($existing['person'], $incomingPerson)) {
                    $existing['person'] .= ', '.$incomingPerson;
                }
            }

            $existing['confidence'] = min($existing['confidence'], $matchedItem['confidence']);
        }
        unset($existing);

        return array_values($deduped);
    }

    /**
     * @param  array<int, array<string, mixed>>  $matchedItems
     */
    private function calculatePricing(array $matchedItems): ParsePricing
    {
        $subtotal = 0.0;
        foreach ($matchedItems as $matchedItem) {
            $subtotal += $matchedItem['subtotal'];
        }

        $subtotal = round($subtotal, 2);
        $tax = round((float) $this->tax($subtotal), 2);
        $netTotal = round($subtotal + $tax, 2);

        return new ParsePricing(
            subtotal: $subtotal,
            tax: $tax,
            netTotal: $netTotal,
        );
    }
}
