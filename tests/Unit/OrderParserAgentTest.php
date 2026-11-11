<?php

namespace Tests\Unit;

use App\Ai\Agents\OrderParserAgent;
use Tests\TestCase;

class OrderParserAgentTest extends TestCase
{
    public function test_instructions_includes_restaurant_name_and_compact_menu(): void
    {
        $menu = [
            [
                'id' => 1,
                'name' => 'Smash Burger',
                'category' => 'Burgers',
                'price' => 120.00,
                'description' => 'Double patty with cheddar',
            ],
            [
                'id' => 2,
                'name' => 'French Fries',
                'category' => 'Sides',
                'price' => 45.00,
                'description' => null,
            ],
            [
                'id' => 3,
                'name' => 'Pepsi Can',
                'category' => '',
                'price' => 25.00,
                'description' => '',
            ],
        ];

        $agent = new OrderParserAgent(restaurantName: 'Burger Palace', menuDigest: $menu);
        $instructions = $agent->instructions();

        $this->assertStringContainsString('Burger Palace', $instructions);
        $this->assertStringContainsString('#1: Smash Burger (Burgers, 120 EGP) - Double patty with cheddar', $instructions);
        $this->assertStringContainsString('#2: French Fries (Sides, 45 EGP)', $instructions);
        $this->assertStringContainsString('#3: Pepsi Can (25 EGP)', $instructions);
        $this->assertStringContainsString('Grounding:', $instructions);
        $this->assertStringContainsString('Egyptian Dialect:', $instructions);
        $this->assertStringContainsString('Franco-Arabic:', $instructions);
        $this->assertStringContainsString('Customizations vs Notes:', $instructions);
        $this->assertStringContainsString('Bill Splitting', $instructions);
    }

    public function test_instructions_handles_empty_menu(): void
    {
        $agent = new OrderParserAgent(restaurantName: 'Empty Cafe', menuDigest: []);
        $instructions = $agent->instructions();

        $this->assertStringContainsString('Empty Cafe', $instructions);
        $this->assertStringContainsString('No items available.', $instructions);
    }
}
