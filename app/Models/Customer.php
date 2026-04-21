<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Customer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name', 'email', 'password', 'phone_number', 'address', 'restaurant_id'
    ];

    protected $hidden = [
        'password',
    ];

    public function orders()
    {
        $this->hasMany(Order::class);
    }
}
