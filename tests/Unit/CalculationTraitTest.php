<?php

namespace Tests\Unit;

use App\Traits\Calculation;
use PHPUnit\Framework\TestCase;

class CalculationTraitTest extends TestCase
{
    private object $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new class
        {
            use Calculation;
        };
    }

    public function test_subtotal_multiplies_price_and_quantity(): void
    {
        $this->assertSame(200.0, (float) $this->calculator->subTotal(100.0, 2));
        $this->assertSame(0.0, (float) $this->calculator->subTotal(0, 5));
    }

    public function test_tax_calculates_fourteen_percent_vat(): void
    {
        $this->assertEqualsWithDelta(14.0, (float) $this->calculator->tax(100.0), 0.0001);
        $this->assertEqualsWithDelta(28.0, (float) $this->calculator->tax(200.0), 0.0001);
    }
}
