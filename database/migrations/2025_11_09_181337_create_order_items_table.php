<?php

use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Order::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Item::class)->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->timestamps();
            $table->softDeletes();
            // Prevent duplicate items in the same order (if needed)
            $table->unique(['order_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
