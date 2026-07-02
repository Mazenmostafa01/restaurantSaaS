<?php

namespace App\Traits;

trait Calculation
{
    public function subTotal($price = 0, $quantity = 1)
    {
        return $price * $quantity;
    }

    public function tax($subTotal)
    {
        return $subTotal * 0.14;
    }
}
