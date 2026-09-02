<?php

namespace App\Tests\Entity;

use App\Entity\Credit;
use PHPUnit\Framework\TestCase;

class CreditTest extends TestCase
{
    public function testComputedAmountsAndProgress(): void
    {
        $credit = (new Credit())
            ->setMontantInitial(200000)
            ->setCapitalRestant(125000)
            ->setMensualite(820.50)
            ->setAssuranceMensuelle(34.20)
        ;

        self::assertSame(75000.0, $credit->getMontantRembourse());
        self::assertSame(37.5, $credit->getProgressPercentage());
        self::assertEqualsWithDelta(854.70, $credit->getCoutMensuel(), 0.001);
    }

    public function testProgressIsKeptInsideValidBounds(): void
    {
        $credit = (new Credit())
            ->setMontantInitial(1000)
            ->setCapitalRestant(-100)
        ;

        self::assertSame(100.0, $credit->getProgressPercentage());

        $credit->setMontantInitial(0);
        self::assertSame(0.0, $credit->getProgressPercentage());
    }
}
