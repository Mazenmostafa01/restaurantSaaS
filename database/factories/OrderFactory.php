<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::query()->value('id'),
            'price' => 100.00,
            'tax' => 14.00,
            'net' => 114.00,
            'type' => 'take_away',
            'note' => null,
        ];
    }
}
