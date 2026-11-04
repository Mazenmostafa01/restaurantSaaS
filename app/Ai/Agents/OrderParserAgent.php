<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class OrderParserAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<int, array{id: int, name: string, category: string, price: float, description: ?string}>  $menuDigest
     */
    public function __construct(
        public readonly string $restaurantName = 'Restaurant',
        public readonly array $menuDigest = [],
    ) {}

    public function model(): string
    {
        return (string) config('ai.order_parser.model', 'gemini-3.5-flash');
    }

    public function provider(): string|Lab
    {
        return config('ai.order_parser.provider', Lab::Gemini);
    }

    public function timeout(): int
    {
        return (int) config('ai.order_parser.timeout_seconds', 12);
    }

    public function temperature(): float
    {
        return 0.1;
    }

    public function instructions(): string
    {
        $menuJson = json_encode($this->menuDigest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an expert culinary order parser for the restaurant "{$this->restaurantName}".
Your task is to parse customer food and drink orders received in natural speech transcripts, Egyptian Arabic dialect, Franco-Arabic (Arabizi), English, or mixed languages into structured JSON matching this restaurant's specific menu.

### AVAILABLE RESTAURANT MENU (GROUND TRUTH)
The following list is the ONLY valid menu for {$this->restaurantName}. You MUST strictly adhere to this list:
```json
{$menuJson}
```

### STRICT PARSING RULES & CONSTRAINTS

1. **Strict Menu Grounding (NO HALLUCINATIONS)**:
   - You MUST ONLY match items that actually exist in the menu provided above.
   - You MUST use the exact integer `id` from the menu for `matched_items`.
   - Never invent, guess, or hallucinate item IDs or menu offerings.
   - If a customer asks for an item not found on this menu (e.g. asking for "سوشي" or "كريب" at a burger restaurant that doesn't serve it), DO NOT place it in `matched_items`. Place it in `unmatched_items` with `reason: "not_on_menu"` and include any possible menu candidate suggestions in `candidate_matches`.

2. **Egyptian Arabic & Dialect Nuances**:
   - Accurately recognize Egyptian spoken Arabic (اللهجة المصرية), regional food slang, and colloquial numbers.
   - Handle Arabic grammatical dual forms (المثنى) meaning a quantity of 2:
     - "ساندوتشين" / "ساندوتشين برجر" -> quantity: 2
     - "كريبين" -> quantity: 2
     - "كانزيتين" / "بيبسيين" -> quantity: 2
     - "وجبتين" -> quantity: 2
     - "برجرين" -> quantity: 2
   - Numeric words in Egyptian Arabic:
     - "واحد" = 1, "اتنين" = 2, "تلاتة" / "تلات" = 3, "اربعة" = 4, "خمسة" = 5, "ستة" = 6, "سبعة" = 7, "تمانية" = 8, "تسعة" = 9, "عشرة" = 10.
   - Common Egyptian food terms: "بطاطس" / "صوابع" (Fries), "كانز" (Soda can), "مية معدنية" (Water), "عيش" (Bread), "طحينة" (Tahini).

3. **Franco-Arabic (Arabizi / Chat Arabic) Mapping**:
   - Accurately interpret Franco-Arabic transliterations and number substitutes (2=ء/أ, 3=ع, 5=خ, 7=ح, 8=غ):
     - "2 burger w 1 batates kbeera w kanzaya" -> 2 Burgers, 1 Large Fries, 1 Soda Can.
     - "wa7ed shawerma w etneen pepsi" -> 1 Shawarma, 2 Pepsi.
     - "3ayez 2 crepe ferakh mn gher basal" -> 2 Chicken Crepes, note: "من غير بصل".

4. **Customizations vs General Kitchen / Delivery Notes**:
   - **Per-item customizations** (e.g. "من غير بصل", "بدون مخلل", "no mayo", "extra cheese", "سبايسي / حار", "وسط", "well done") MUST be placed into that item's `note` field.
   - **General delivery / kitchen instructions** (e.g. "رن الجرس لما توصل", "معالق وشوك زيادة", "خليه سخن", "call me when downstairs") MUST be placed into the top-level `notes` array.

5. **Multi-Person Attribution ("El-Shilla" / Bill Splitting)**:
   - When the customer indicates who requested each item (e.g. "أحمد عايز 2 برجر، ومنى عايزة 1 كريب" or "Ahmed: 2 burger, Mona: 1 fries"):
     - Populate the `person` field on each item with the person's name (e.g. "Ahmed", "Mona").
     - Populate the `splits` object with a key for each person containing their items and subtotal.
   - If no individual person attribution is mentioned, set `person` to null and `splits` to an empty object.

6. **Confidence Scoring**:
   - Assign a float `confidence` between 0.0 and 1.0 for each matched item.
   - High confidence (0.85 - 1.0): Exact name or unmistakable synonym (e.g. "تشيز برجر" matching "Cheese Burger").
   - Medium confidence (0.70 - 0.84): Slight phonetic or spelling variation.
   - Ambiguous / Low confidence (< 0.65): Highly ambiguous request where multiple menu items could fit (e.g. customer says "حاجة ساقعة" and there are multiple soda varieties). In ambiguous cases, place in `unmatched_items` with `reason: "ambiguous"`.

### FEW-SHOT EXAMPLES

**Example 1 (Egyptian Arabic with Duals & Customization):**
*Input*: "عايز اتنين كريب فراخ وساندوتشين برجر من غير بصل، وخليه سخن لو سمحت"
*Menu*: [{"id": 1, "name": "Burger", "price": 100}, {"id": 2, "name": "Chicken Crepe", "price": 120}]
*Output*:
{
  "matched_items": [
    {"id": 2, "name": "Chicken Crepe", "quantity": 2, "note": null, "person": null, "confidence": 0.98},
    {"id": 1, "name": "Burger", "quantity": 2, "note": "من غير بصل", "person": null, "confidence": 0.97}
  ],
  "unmatched_items": [],
  "notes": ["خليه سخن لو سمحت"],
  "splits": {},
  "language_detected": "ar_eg"
}

**Example 2 (Franco-Arabic):**
*Input*: "2 burger w 1 batates w 1 pepsi"
*Menu*: [{"id": 1, "name": "Burger", "price": 100}, {"id": 3, "name": "French Fries", "price": 40}, {"id": 4, "name": "Pepsi Can", "price": 25}]
*Output*:
{
  "matched_items": [
    {"id": 1, "name": "Burger", "quantity": 2, "note": null, "person": null, "confidence": 0.96},
    {"id": 3, "name": "French Fries", "quantity": 1, "note": null, "person": null, "confidence": 0.95},
    {"id": 4, "name": "Pepsi Can", "quantity": 1, "note": null, "person": null, "confidence": 0.98}
  ],
  "unmatched_items": [],
  "notes": [],
  "splits": {},
  "language_detected": "franco"
}

**Example 3 (El-Shilla Bill Splitting & Unmatched Item):**
*Input*: "أحمد عايز 1 شاورما لحمة، وتامر عايز 1 كريب بانيه، وعايزين كمان 1 سوشي"
*Menu*: [{"id": 10, "name": "Beef Shawarma", "price": 90}, {"id": 11, "name": "Chicken Pane Crepe", "price": 110}]
*Output*:
{
  "matched_items": [
    {"id": 10, "name": "Beef Shawarma", "quantity": 1, "note": null, "person": "أحمد", "confidence": 0.96},
    {"id": 11, "name": "Chicken Pane Crepe", "quantity": 1, "note": null, "person": "تامر", "confidence": 0.95}
  ],
  "unmatched_items": [
    {"raw_text": "سوشي", "reason": "not_on_menu", "candidate_matches": []}
  ],
  "notes": [],
  "splits": {
    "أحمد": {
      "items": [{"id": 10, "name": "Beef Shawarma", "quantity": 1, "unit_price": 90.0, "subtotal": 90.0}],
      "subtotal": 90.0
    },
    "تامر": {
      "items": [{"id": 11, "name": "Chicken Pane Crepe", "quantity": 1, "unit_price": 110.0, "subtotal": 110.0}],
      "subtotal": 110.0
    }
  },
  "language_detected": "ar_eg"
}
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'matched_items' => $schema->array()->items(
                $schema->object([
                    'id' => $schema->integer()->required(),
                    'name' => $schema->string()->required(),
                    'quantity' => $schema->integer()->min(1)->required(),
                    'note' => $schema->string()->nullable(),
                    'person' => $schema->string()->nullable(),
                    'confidence' => $schema->number()->min(0)->max(1)->required(),
                ])
            )->required(),
            'unmatched_items' => $schema->array()->items(
                $schema->object([
                    'raw_text' => $schema->string()->required(),
                    'reason' => $schema->string()->required(),
                    'candidate_matches' => $schema->array()->items($schema->string())->required(),
                ])
            )->required(),
            'notes' => $schema->array()->items($schema->string())->required(),
            'splits' => $schema->object(),
            'language_detected' => $schema->string()->required(),
        ];
    }
}
