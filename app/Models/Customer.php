<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name', 'email', 'password', 'phone_number', 'address',
    ];

    protected $hidden = [
        'password',
    ];

    public function order()
    {
        $this->hasMany(Order::class);
    }
}
