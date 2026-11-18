<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class OrderSequenceService
{
    public function businessDate(): string
    {
        return now(config('tenant.business_timezone', 'Africa/Cairo'))->toDateString();
    }

    /**
     * Ensure the counter row exists before starting the order transaction.
     */
    public function ensureCounterRow(int $restaurantId, string $date): void
    {
        DB::table('order_daily_sequences')->insertOrIgnore([
            'restaurant_id' => $restaurantId,
            'sequence_date' => $date,
            'last_number' => 0,
        ]);
    }

    /**
     * Atomically increment and return the next daily number inside the order transaction.
     */
    public function claimNext(int $restaurantId, string $date): int
    {
        $affected = DB::table('order_daily_sequences')
            ->where('restaurant_id', $restaurantId)
            ->where('sequence_date', $date)
            ->increment('last_number');

        if ($affected === 0) {
            $this->ensureCounterRow($restaurantId, $date);

            DB::table('order_daily_sequences')
                ->where('restaurant_id', $restaurantId)
                ->where('sequence_date', $date)
                ->increment('last_number');
        }

        return (int) DB::table('order_daily_sequences')
            ->where('restaurant_id', $restaurantId)
            ->where('sequence_date', $date)
            ->value('last_number');
    }
}
