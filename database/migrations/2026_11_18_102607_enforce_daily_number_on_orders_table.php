<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->date('order_date')->nullable(false)->change();
            $table->unsignedInteger('daily_number')->nullable(false)->change();
            $table->unique(['restaurant_id', 'order_date', 'daily_number'], 'uq_orders_tenant_date_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('uq_orders_tenant_date_number');
            $table->date('order_date')->nullable()->change();
            $table->unsignedInteger('daily_number')->nullable()->change();
        });
    }
};
