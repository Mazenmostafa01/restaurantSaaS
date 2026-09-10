<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\ItemCategoryEnum;
use App\Models\Item;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $restaurants = Restaurant::all();

        if ($restaurants->isEmpty()) {
            return;
        }

        $sampleItems = [
            [
                'name' => 'Margherita Pizza',
                'category' => ItemCategoryEnum::FOOD->value,
                'price' => 140.00,
                'description' => 'Classic pizza with tomato sauce, fresh mozzarella, and aromatic basil leaves.',
            ],
            [
                'name' => 'Smash Beef Burger',
                'category' => ItemCategoryEnum::FOOD->value,
                'price' => 175.50,
                'description' => 'Double smashed beef patties with melted cheddar, pickles, and house special sauce in a brioche bun.',
            ],
            [
                'name' => 'Crispy Chicken Tender Platter',
                'category' => ItemCategoryEnum::FOOD->value,
                'price' => 155.00,
                'description' => 'Hand-breaded golden crispy chicken tenders served with honey mustard and seasoned fries.',
            ],
            [
                'name' => 'Creamy Alfredo Pasta',
                'category' => ItemCategoryEnum::FOOD->value,
                'price' => 165.00,
                'description' => 'Fettuccine pasta tossed in rich parmesan cream sauce with garlic and herbs.',
            ],
            [
                'name' => 'Caesar Salad',
                'category' => ItemCategoryEnum::FOOD->value,
                'price' => 95.00,
                'description' => 'Crisp romaine lettuce, herb croutons, parmesan shavings, and house-made creamy Caesar dressing.',
            ],
            [
                'name' => 'Iced Spanish Latte',
                'category' => ItemCategoryEnum::BEVERAGE->value,
                'price' => 65.00,
                'description' => 'Espresso poured over condensed milk and chilled fresh milk with ice.',
            ],
            [
                'name' => 'Fresh Orange Juice',
                'category' => ItemCategoryEnum::BEVERAGE->value,
                'price' => 45.00,
                'description' => '100% freshly squeezed seasonal oranges without added sugar.',
            ],
            [
                'name' => 'Mint Lemonade',
                'category' => ItemCategoryEnum::BEVERAGE->value,
                'price' => 50.00,
                'description' => 'Refreshing blend of crushed fresh lemons, mint leaves, and crushed ice.',
            ],
        ];

        foreach ($restaurants as $restaurant) {
            foreach ($sampleItems as $itemData) {
                // In seeders, explicitly set restaurant_id and bypass global scope check
                Item::withoutGlobalScopes()->firstOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name' => $itemData['name'],
                    ],
                    [
                        'price' => $itemData['price'],
                        'category' => $itemData['category'],
                        'description' => $itemData['description'],
                    ]
                );
            }
        }
    }
}
