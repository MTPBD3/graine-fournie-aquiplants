<?php

namespace App\Tests\Unit;

use App\Entity\HistoGfDeposee;
use PHPUnit\Framework\TestCase;

class StatutTransitionTest extends TestCase
{
    private const TRANSITIONS_VALIDES = [
        'en_attente' => ['en_stock', 'epuise'],
        'en_stock'   => ['epuise'],
        'epuise'     => [],
    ];

    private function transitionEstValide(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS_VALIDES[$from] ?? [], true);
    }

    public function testPassageEnAttenteVersEnStockEstValide(): void
    {
        $this->assertTrue($this->transitionEstValide('en_attente', 'en_stock'));
    }

    public function testPassageEnAttenteVersEpuiseEstValide(): void
    {
        $this->assertTrue($this->transitionEstValide('en_attente', 'epuise'));
    }

    public function testPassageEnStockVersEpuiseEstValide(): void
    {
        $this->assertTrue($this->transitionEstValide('en_stock', 'epuise'));
    }

    public function testPassageEnStockVersEnAttenteEstInvalide(): void
    {
        $this->assertFalse($this->transitionEstValide('en_stock', 'en_attente'));
    }

    public function testPassageEpuiseVersEnAttenteEstInvalide(): void
    {
        $this->assertFalse($this->transitionEstValide('epuise', 'en_attente'));
    }

    public function testPassageEpuiseVersEnStockEstInvalide(): void
    {
        $this->assertFalse($this->transitionEstValide('epuise', 'en_stock'));
    }

    public function testEntiteAccepteStatutValide(): void
    {
        $h = new HistoGfDeposee();
        $h->setStatut('en_stock');
        $this->assertSame('en_stock', $h->getStatut());
    }

    public function testStatutsValidesSontAuNombreDeTrois(): void
    {
        $this->assertCount(3, self::TRANSITIONS_VALIDES);
    }
}
