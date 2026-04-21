<?php

use App\Models\Restaurant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── users ─────────────────────────────────────────────────────────────
        // nullable: a user with restaurant_id = null is a platform superadmin
        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Restaurant::class)
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->nullOnDelete();

            // Superadmin look-ups by restaurant
            $table->index('restaurant_id', 'idx_users_restaurant');
        });

        // ── items ─────────────────────────────────────────────────────────────
        // Composite index: supports  WHERE restaurant_id = ? AND deleted_at IS NULL
        // at any table size — critical for the menu page at 2 000+ orders/day
        Schema::table('items', function (Blueprint $table) {
            $table->foreignIdFor(Restaurant::class)
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->nullOnDelete();

            $table->index(['restaurant_id', 'deleted_at'], 'idx_items_restaurant_deleted');
        });

        // ── orders ────────────────────────────────────────────────────────────
        // Composite index: supports  WHERE restaurant_id = ? AND created_at BETWEEN ? AND ?
        // The dashboard SUM/COUNT queries hit this index — keeps them sub-millisecond
        // even with tens of millions of rows per restaurant per year
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignIdFor(Restaurant::class)
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->nullOnDelete();

            $table->index(['restaurant_id', 'created_at'], 'idx_orders_restaurant_date');
        });

        // ── customers ─────────────────────────────────────────────────────────
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignIdFor(Restaurant::class)
                  ->nullable()
                  ->after('id')
                  ->constrained()
                  ->nullOnDelete();

            $table->index(['restaurant_id', 'id'], 'idx_customers_restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_restaurant');
            $table->dropConstrainedForeignId('restaurant_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_restaurant_deleted');
            $table->dropConstrainedForeignId('restaurant_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_restaurant_date');
            $table->dropConstrainedForeignId('restaurant_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_restaurant_id');
            $table->dropConstrainedForeignId('restaurant_id');
        });
    }
};
