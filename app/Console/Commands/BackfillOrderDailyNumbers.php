<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOrderDailyNumbers extends Command
{
    protected $signature = 'orders:backfill-daily-numbers';

    protected $description = 'Backfill order_date and daily_number for existing orders and sync daily sequence counters.';

    public function handle(): int
    {
        $timezone = (string) config('tenant.business_timezone', 'Africa/Cairo');
        $restaurantIds = Restaurant::query()->pluck('id');
        $totalUpdated = 0;

        foreach ($restaurantIds as $restaurantId) {
            $restaurantId = (int) $restaurantId;

            /** @var array<string, int> $dateCounters */
            $dateCounters = [];

            // Bypass of TenantScope is intentional: this console command backfills orders across all tenants.
            Order::withoutGlobalScopes()
                ->withTrashed()
                ->where('restaurant_id', $restaurantId)
                ->where(function ($query): void {
                    $query->whereNull('daily_number')->orWhereNull('order_date');
                })
                ->orderBy('id')
                ->chunkById(500, function ($orders) use ($restaurantId, $timezone, &$dateCounters, &$totalUpdated): void {
                    foreach ($orders as $order) {
                        $orderDate = ($order->created_at ?? now())
                            ->copy()
                            ->timezone($timezone)
                            ->toDateString();

                        if (! array_key_exists($orderDate, $dateCounters)) {
                            $existingMax = Order::withoutGlobalScopes()
                                ->withTrashed()
                                ->where('restaurant_id', $restaurantId)
                                ->where('order_date', $orderDate)
                                ->max('daily_number');

                            $dateCounters[$orderDate] = $existingMax !== null ? (int) $existingMax : 0;
                        }

                        $dateCounters[$orderDate]++;
                        $dailyNumber = $dateCounters[$orderDate];

                        DB::table('orders')
                            ->where('id', $order->id)
                            ->update([
                                'order_date' => $orderDate,
                                'daily_number' => $dailyNumber,
                            ]);

                        $totalUpdated++;
                    }
                });

            $this->syncSequenceCountersForRestaurant($restaurantId);
        }

        $remainingNulls = Order::withoutGlobalScopes()
            ->withTrashed()
            ->where(function ($query): void {
                $query->whereNull('daily_number')->orWhereNull('order_date');
            })
            ->count();

        if ($remainingNulls > 0) {
            $this->error("Backfill incomplete: {$remainingNulls} order(s) still have NULL order_date or daily_number.");

            return self::FAILURE;
        }

        $this->info("Backfill complete. Updated {$totalUpdated} order(s).");

        return self::SUCCESS;
    }

    private function syncSequenceCountersForRestaurant(int $restaurantId): void
    {
        $maxByDate = Order::withoutGlobalScopes()
            ->withTrashed()
            ->where('restaurant_id', $restaurantId)
            ->whereNotNull('order_date')
            ->whereNotNull('daily_number')
            ->selectRaw('order_date, MAX(daily_number) as max_number')
            ->groupBy('order_date')
            ->get();

        foreach ($maxByDate as $row) {
            $date = (string) $row->order_date;
            $maxNumber = (int) $row->max_number;

            DB::table('order_daily_sequences')->insertOrIgnore([
                'restaurant_id' => $restaurantId,
                'sequence_date' => $date,
                'last_number' => $maxNumber,
            ]);

            DB::table('order_daily_sequences')
                ->where('restaurant_id', $restaurantId)
                ->where('sequence_date', $date)
                ->where('last_number', '<', $maxNumber)
                ->update(['last_number' => $maxNumber]);
        }
    }
}
