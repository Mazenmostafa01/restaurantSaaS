<?php

namespace App\Models;

use App\Services\OrderSequenceService;
use App\Services\TenantContext;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_date',
        'daily_number',
        'price',
        'tax',
        'net',
        'type',
        'note',
        'user_id',
        'customer_id',
        'restaurant_id',
    ];

    protected function casts(): array
    {
        return [
            'daily_number' => 'integer',
            'price' => 'decimal:2',
            'tax' => 'decimal:2',
            'net' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if ($order->order_date === null) {
                $order->order_date = now(config('tenant.business_timezone', 'Africa/Cairo'))->toDateString();
            }

            if ($order->daily_number === null) {
                $restaurantId = $order->restaurant_id ?? app(TenantContext::class)->id();
                if ($restaurantId !== null) {
                    $sequence = app(OrderSequenceService::class);
                    $sequence->ensureCounterRow((int) $restaurantId, (string) $order->order_date);
                    $order->daily_number = $sequence->claimNext((int) $restaurantId, (string) $order->order_date);
                } else {
                    $order->daily_number = 1;
                }
            }

            if ($order->order_number === null || $order->order_number === '') {
                $order->order_number = (string) $order->daily_number;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'order_items')->withPivot(['quantity'])->withTrashed();
    }
}
