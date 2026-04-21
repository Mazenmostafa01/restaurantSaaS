<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        // We only want to seed a default restaurant if none exist
        if (Restaurant::count() > 0) {
            return;
        }

        // Create a default restaurant
        $restaurant = Restaurant::create([
            'name' => 'Main Restaurant',
            'email' => 'contact@mainrestaurant.com',
            'phone' => '123-456-7890',
            'address' => '123 Main Street',
        ]);

        // Temporarily disable TenantScope if we want to query globally, 
        // though in seeder context it shouldn't be active unless middleware ran.
        
        // Assign existing records to this restaurant
        DB::table('items')->whereNull('restaurant_id')->update(['restaurant_id' => $restaurant->id]);
        DB::table('orders')->whereNull('restaurant_id')->update(['restaurant_id' => $restaurant->id]);
        DB::table('customers')->whereNull('restaurant_id')->update(['restaurant_id' => $restaurant->id]);
        DB::table('users')->whereNull('restaurant_id')->update(['restaurant_id' => $restaurant->id]);
        
        // Note: The above assigns ALL users to this restaurant. If there's a specific superadmin user, 
        // they can manually set their restaurant_id to null in the database to see all data.
    }
}
