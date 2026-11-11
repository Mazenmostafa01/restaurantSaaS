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
        return (int) config('ai.order_parser.timeout_seconds', 30);
    }

    public function temperature(): float
    {
        return 0.1;
    }

    public function instructions(): string
    {
        $menu = $this->formatMenu();

        return <<<PROMPT
Parse customer orders for "{$this->restaurantName}" into structured JSON matching the menu. Orders can be in Egyptian Arabic, Franco-Arabic, English, or mixed.

### MENU
{$menu}

### RULES
1. Grounding: Match ONLY menu items above by exact integer `id`. If missing or ambiguous, put in `unmatched_items` with reason "not_on_menu" or "ambiguous". Never invent IDs.
2. Egyptian Dialect: Dual nouns (suffix ين/-ين e.g. ساندوتشين, كريبين, كانزيتين, وجبتين) = quantity 2. Colloquial numbers: واحد=1, اتنين=2, تلاتة=3, اربعة=4, خمسة=5, ستة=6, سبعة=7, تمانية=8, تسعة=9, عشرة=10. Slang: بطاطس/صوابع=fries, كانز=soda, مية=water, عيش=bread.
3. Franco-Arabic: 2=ء/أ, 3=ع, 5=خ, 7=ح, 8=غ (e.g. "wa7ed burger w 2 batates" = 1 Burger, 2 Fries; "3ayez" = wants).
4. Customizations vs Notes: Put item-specific requests (e.g. "من غير بصل", "no mayo", "extra cheese") in item `note`. Put general delivery/kitchen instructions (e.g. "خليه سخن", "معالق زيادة", "call on arrival") in top-level `notes` array.
5. Bill Splitting ("El-Shilla"): If items belong to specific people (e.g. "Ahmed: 2 burgers, Mona: 1 fries" or "أحمد عايز... وتامر عايز..."), set `person` on each item and attribute items in `splits`.
6. Confidence: 0.0-1.0 (>=0.85 clear match, <0.65 ambiguous -> unmatched_items).

### EXAMPLES
Input: "عايز اتنين كريب فراخ وساندوتشين برجر من غير بصل، وخليه سخن"
-> matched: [#2 Chicken Crepe qty:2, #1 Burger qty:2 note:"من غير بصل"], notes: ["خليه سخن"], lang: "ar_eg"

Input: "2 burger w 1 batates w 1 pepsi"
-> matched: [#1 Burger qty:2, #3 Fries qty:1, #4 Pepsi qty:1], lang: "franco"

Input: "أحمد عايز 1 شاورما، وتامر عايز 1 كريب، وعايزين 1 سوشي" (#10 Shawarma, #11 Crepe on menu; Sushi not on menu)
-> matched: [#10 Shawarma qty:1 person:"أحمد", #11 Crepe qty:1 person:"تامر"], unmatched: ["سوشي" reason:"not_on_menu"], splits: {"أحمد": [...], "تامر": [...]}, lang: "ar_eg"
PROMPT;
    }

    protected function formatMenu(): string
    {
        if (empty($this->menuDigest)) {
            return 'No items available.';
        }

        $lines = [];
        foreach ($this->menuDigest as $item) {
            $id = $item['id'] ?? '';
            $name = $item['name'] ?? '';
            $details = [];

            if (! empty($item['category'])) {
                $details[] = $item['category'];
            }
            if (isset($item['price']) && (float) $item['price'] > 0) {
                $details[] = number_format((float) $item['price'], 0).' EGP';
            }

            $meta = ! empty($details) ? ' ('.implode(', ', $details).')' : '';
            $desc = ! empty($item['description']) ? ' - '.trim((string) $item['description']) : '';

            $lines[] = "#{$id}: {$name}{$meta}{$desc}";
        }

        return implode("\n", $lines);
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
