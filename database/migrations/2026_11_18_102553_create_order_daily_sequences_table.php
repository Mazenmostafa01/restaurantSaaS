<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_daily_sequences', function (Blueprint $table): void {
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('sequence_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->primary(['restaurant_id', 'sequence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_daily_sequences');
    }
};
