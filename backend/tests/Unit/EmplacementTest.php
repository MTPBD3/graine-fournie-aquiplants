<?php

namespace App\Tests\Unit;

use App\Entity\Client;
use App\Entity\Emplacement;
use App\Entity\GfClient;
use App\Entity\Plant;
use PHPUnit\Framework\TestCase;

class EmplacementTest extends TestCase
{
    private function makeEmplacement(string $lettre = 'A', int $etage = 1): Emplacement
    {
        $e = new Emplacement();
        $e->setLettreEtagere($lettre);
        $e->setNumeroEtage($etage);
        return $e;
    }

    private function makeSachet(?Emplacement $emplacement = null): GfClient
    {
        $client = new Client();
        $client->setNomClient('Dupont')->setPrenomClient('Jean');

        $plant = new Plant();
        $plant->setNomPlant('Rosa canina');

        $sachet = new GfClient();
        $sachet->setNumeroLot('LOT-TEST');
        $sachet->setNomClient('Dupont');
        $sachet->setQuantiteDisponible(50);
        $sachet->setSeuilAlerte(5);
        $sachet->setClient($client);
        $sachet->setPlant($plant);

        if ($emplacement !== null) {
            $sachet->setEmplacement($emplacement);
        }

        return $sachet;
    }

    public function testNouvelEmplacementEstLibre(): void
    {
        $e = $this->makeEmplacement();
        $this->assertTrue($e->getSachets()->isEmpty());
    }

    public function testEmplacementStockeLettreEtEtage(): void
    {
        $e = $this->makeEmplacement('C', 3);
        $this->assertSame('C', $e->getLettreEtagere());
        $this->assertSame(3, $e->getNumeroEtage());
    }

    public function testAssignationLieGfClientAEmplacement(): void
    {
        $e = $this->makeEmplacement('B', 2);
        $sachet = $this->makeSachet($e);

        $this->assertSame($e, $sachet->getEmplacement());
        $this->assertNotNull($sachet->getEmplacement());
    }

    public function testLiberationDetacheLEmplacement(): void
    {
        $e = $this->makeEmplacement();
        $sachet = $this->makeSachet($e);

        $sachet->setEmplacement(null);

        $this->assertNull($sachet->getEmplacement());
    }

    public function testSansAssignationEmplacementEstNull(): void
    {
        $sachet = $this->makeSachet();
        $this->assertNull($sachet->getEmplacement());
    }
}
