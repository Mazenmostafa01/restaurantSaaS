<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->change();
            $table->decimal('tax', 12, 2)->change();
            $table->decimal('net', 12, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->change();
            $table->decimal('tax', 8, 2)->change();
            $table->decimal('net', 8, 2)->change();
        });
    }
};
