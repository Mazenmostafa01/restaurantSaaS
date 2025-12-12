<?php

namespace App\Traits;

trait Calculation
{
    public function subTotal($price = 0, $quantity = 1)
    {
        $subTotal = $price * $quantity;

        return $subTotal;
    }

    public function tax($subTotal)
    {
        $tax = $subTotal * 0.14;

        return $tax;
    }
}
