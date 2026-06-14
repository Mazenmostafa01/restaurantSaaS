<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop the old global unique constraints
            $table->dropUnique(['email']);
            $table->dropUnique(['phone_number']);

            // Add Sanctum/auth fields
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->rememberToken()->after('email_verified_at');

            // Composite unique: same email/phone can exist across different restaurants
            $table->unique(['restaurant_id', 'email'], 'customers_restaurant_email_unique');
            $table->unique(['restaurant_id', 'phone_number'], 'customers_restaurant_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_restaurant_email_unique');
            $table->dropUnique('customers_restaurant_phone_unique');

            $table->dropColumn(['email_verified_at', 'remember_token']);

            // Restore global unique constraints
            $table->unique('email');
            $table->unique('phone_number');
        });
    }
};
