<?php

namespace Tests\Unit;

use App\DTOs\AudioPayload;
use App\DTOs\OrderParseData;
use App\DTOs\OrderWriteData;
use App\DTOs\ParsedOrderData;
use App\DTOs\ParseMetadata;
use App\DTOs\ParsePricing;
use App\Http\Resources\ParsedOrderResource;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class ParsedOrderDtoTest extends TestCase
{
    public function test_order_parse_data_throws_when_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OrderParseData(text: '   ', audio: null);
    }

    public function test_order_parse_data_from_customer_request(): void
    {
        $request = Request::create('/api/orders/parse', 'POST', [
            'text' => '2 burgers and 1 cola',
            'preferred_language' => 'ar_eg',
        ]);

        $dto = OrderParseData::fromCustomerRequest($request);

        $this->assertSame('2 burgers and 1 cola', $dto->text);
        $this->assertSame('ar_eg', $dto->preferredLanguage);
        $this->assertSame('customer_web', $dto->channel);
        $this->assertFalse($dto->hasAudio());
    }

    public function test_order_parse_data_from_whatsapp(): void
    {
        $dto = OrderParseData::fromWhatsApp(
            text: null,
            audioBase64: 'dGVzdGF1ZGlv',
            audioMimeType: 'audio/ogg',
            audioDurationSeconds: 15,
            preferredLanguage: 'ar_eg'
        );

        $this->assertTrue($dto->hasAudio());
        $this->assertInstanceOf(AudioPayload::class, $dto->audio);
        $this->assertSame('whatsapp_group', $dto->channel);
        $this->assertSame('audio/ogg', $dto->audio->mimeType);
        $this->assertSame(15, $dto->audio->durationSeconds);
    }

    public function test_parsed_order_data_combines_and_truncates_notes(): void
    {
        $parsed = new ParsedOrderData(
            matchedItems: [
                [
                    'id' => 1,
                    'name' => 'Burger',
                    'quantity' => 2,
                    'unit_price' => 100.0,
                    'subtotal' => 200.0,
                    'note' => 'extra cheese',
                    'person' => 'Ahmed',
                    'confidence' => 0.95,
                ],
                [
                    'id' => 2,
                    'name' => 'Fries',
                    'quantity' => 1,
                    'unit_price' => 40.0,
                    'subtotal' => 40.0,
                    'note' => null,
                    'person' => 'Mona',
                    'confidence' => 0.98,
                ],
            ],
            unmatchedItems: [],
            pricing: new ParsePricing(
                subtotal: 240.0,
                tax: 33.6,
                netTotal: 273.6
            ),
            metadata: new ParseMetadata(
                notes: ['call when downstairs', 'bring ketchup'],
                confidenceScore: 0.96,
                languageDetected: 'ar_eg'
            )
        );

        $this->assertTrue($parsed->hasMatchedItems());
        $this->assertFalse($parsed->hasUnmatchedItems());
        $this->assertSame(240.0, $parsed->subtotal());
        $this->assertSame(33.6, $parsed->tax());
        $this->assertSame(273.6, $parsed->netTotal());
        $this->assertSame('call when downstairs | bring ketchup | Burger: extra cheese', $parsed->combinedKitchenNote());
    }

    public function test_order_write_data_can_be_built_from_parsed_order(): void
    {
        $parsed = new ParsedOrderData(
            matchedItems: [
                [
                    'id' => 10,
                    'name' => 'Pizza',
                    'quantity' => 2,
                    'unit_price' => 120.0,
                    'subtotal' => 240.0,
                    'note' => 'thin crust',
                    'person' => null,
                    'confidence' => 0.9,
                ],
                [
                    'id' => 10,
                    'name' => 'Pizza',
                    'quantity' => 1,
                    'unit_price' => 120.0,
                    'subtotal' => 120.0,
                    'note' => null,
                    'person' => null,
                    'confidence' => 0.9,
                ],
            ],
            unmatchedItems: [],
            pricing: new ParsePricing(
                subtotal: 360.0,
                tax: 50.4,
                netTotal: 410.4
            ),
            metadata: new ParseMetadata(
                notes: ['hot please'],
                confidenceScore: 0.9,
                languageDetected: 'en'
            )
        );

        $writeDto = OrderWriteData::fromParsedOrder(
            parsedOrder: $parsed,
            userId: null,
            customerId: 77,
            type: 'take_away',
            orderNumberPrefix: 'AI-'
        );

        $this->assertSame('take_away', $writeDto->type);
        $this->assertSame(77, $writeDto->customerId);
        $this->assertSame('AI-', $writeDto->orderNumberPrefix);
        $this->assertSame([10 => 3], $writeDto->itemQuantities);
        $this->assertSame('hot please | Pizza: thin crust', $writeDto->note);
    }

    public function test_parsed_order_resource_formats_decimals_and_structures_cleanly(): void
    {
        $parsed = new ParsedOrderData(
            matchedItems: [
                [
                    'id' => 5,
                    'name' => 'Shawarma',
                    'quantity' => 1,
                    'unit_price' => 85.0,
                    'subtotal' => 85.0,
                    'note' => null,
                    'person' => 'Tarek',
                    'confidence' => 0.99,
                ],
            ],
            unmatchedItems: [
                [
                    'raw_text' => 'koshary',
                    'reason' => 'not_on_menu',
                    'candidate_matches' => [],
                ],
            ],
            pricing: new ParsePricing(
                subtotal: 85.0,
                tax: 11.9,
                netTotal: 96.9
            ),
            metadata: new ParseMetadata(
                notes: ['extra tahini'],
                splits: [
                    'Tarek' => [
                        'items' => [
                            [
                                'id' => 5,
                                'name' => 'Shawarma',
                                'quantity' => 1,
                                'unit_price' => 85.0,
                                'subtotal' => 85.0,
                            ],
                        ],
                        'subtotal' => 85.0,
                    ],
                ]
            )
        );

        $resource = new ParsedOrderResource($parsed);
        $array = $resource->toArray(Request::create('/test'));

        $this->assertSame('85.00', $array['subtotal']);
        $this->assertSame('11.90', $array['tax']);
        $this->assertSame('96.90', $array['net']);
        $this->assertSame('85.00', $array['items'][0]['price']);
        $this->assertSame('85.00', $array['items'][0]['subtotal']);
        $this->assertSame('extra tahini', $array['note']);
        $this->assertSame('koshary', $array['unmatched'][0]['raw_text']);
        $this->assertArrayHasKey('splits', $array);
        $this->assertArrayNotHasKey('confidence_score', $array);
        $this->assertArrayNotHasKey('processing_duration_ms', $array);
    }
}
