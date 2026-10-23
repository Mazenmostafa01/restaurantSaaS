<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // Index for customer order history lookup and filesort elimination:
            // WHERE restaurant_id = ? AND customer_id = ? ORDER BY created_at DESC
            $table->index(['restaurant_id', 'customer_id', 'created_at'], 'idx_orders_tenant_customer_date');

            // Index for sub-millisecond admin order search:
            // WHERE restaurant_id = ? AND order_number LIKE ?
            $table->index(['restaurant_id', 'order_number'], 'idx_orders_tenant_order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('idx_orders_tenant_customer_date');
            $table->dropIndex('idx_orders_tenant_order_number');
        });
    }
};
