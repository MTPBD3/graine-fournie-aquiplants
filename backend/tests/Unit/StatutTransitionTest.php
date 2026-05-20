<?php

namespace App\Tests\Unit;

use App\Entity\HistoGfDeposee;
use PHPUnit\Framework\TestCase;

class StatutTransitionTest extends TestCase
{
    private const TRANSITIONS_VALIDES = [
        'a_traiter' => ['range'],
        'range'     => [],
    ];

    private function transitionEstValide(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS_VALIDES[$from] ?? [], true);
    }

    public function testPassageATraiterVersRangeEstValide(): void
    {
        $this->assertTrue($this->transitionEstValide('a_traiter', 'range'));
    }

    public function testPassageRangeVersATraiterEstInvalide(): void
    {
        $this->assertFalse($this->transitionEstValide('range', 'a_traiter'));
    }

    public function testEntiteAccepteStatutValide(): void
    {
        $h = new HistoGfDeposee();
        $h->setStatut('range');
        $this->assertSame('range', $h->getStatut());
    }

    public function testStatutsValidesSontAuNombreDeDeux(): void
    {
        $this->assertCount(2, self::TRANSITIONS_VALIDES);
    }
}
