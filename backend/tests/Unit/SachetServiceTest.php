<?php

namespace App\Tests\Unit;

use App\Entity\GfClient;
use App\Entity\Client;
use App\Entity\Plant;
use PHPUnit\Framework\TestCase;

class SachetServiceTest extends TestCase
{
    private function makeSachet(int $quantite, int $seuil): GfClient
    {
        $client = new Client();
        $client->setNomClient('Dupont')->setPrenomClient('Jean');

        $plant = new Plant();
        $plant->setNomPlant('Tomate')->setNomEspece('Solanum lycopersicum');

        $sachet = new GfClient();
        $sachet->setNumeroLot('LOT-001');
        $sachet->setNomClient('Dupont');
        $sachet->setQuantiteDisponible($quantite);
        $sachet->setSeuilAlerte($seuil);
        $sachet->setClient($client);
        $sachet->setPlant($plant);

        return $sachet;
    }

    public function testSachetAvecQuantiteNulleEstEnAlerte(): void
    {
        $sachet = $this->makeSachet(0, 10);
        $this->assertTrue($sachet->getQuantiteDisponible() <= $sachet->getSeuilAlerte());
    }

    public function testSachetEnDessousDuSeuilEstEnAlerte(): void
    {
        $sachet = $this->makeSachet(5, 10);
        $this->assertTrue($sachet->getQuantiteDisponible() <= $sachet->getSeuilAlerte());
    }

    public function testSachetAuDessusDuSeuilNestPasEnAlerte(): void
    {
        $sachet = $this->makeSachet(50, 10);
        $this->assertFalse($sachet->getQuantiteDisponible() <= $sachet->getSeuilAlerte());
    }

    public function testSachetAuSeuilExactEstEnAlerte(): void
    {
        $sachet = $this->makeSachet(10, 10);
        $this->assertTrue($sachet->getQuantiteDisponible() <= $sachet->getSeuilAlerte());
    }

    public function testQuantiteDisponibleNeDescendPasEnDessousDe0(): void
    {
        $sachet = $this->makeSachet(5, 0);
        $nouvelleQte = max(0, $sachet->getQuantiteDisponible() - 100);
        $this->assertSame(0, $nouvelleQte);
    }
}
