<?php

namespace App\Tests\Unit;

use App\Entity\Client;
use App\Entity\Espece;
use App\Entity\GfClient;
use App\Entity\GfHistoClient;
use App\Entity\Plant;
use App\Entity\Uv;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class EntityGettersTest extends TestCase
{
    // ── Espece ────────────────────────────────────────────────────────────────

    public function testEspeceGetUvsEtGetPlants(): void
    {
        $e = new Espece();
        $e->setNomEspece('Rosa canina');

        $this->assertNull($e->getIdEspece());
        $this->assertSame('Rosa canina', $e->getNomEspece());
        $this->assertInstanceOf(Collection::class, $e->getUvs());
        $this->assertInstanceOf(Collection::class, $e->getPlants());
        $this->assertTrue($e->getUvs()->isEmpty());
        $this->assertTrue($e->getPlants()->isEmpty());
    }

    // ── Plant ─────────────────────────────────────────────────────────────────

    public function testPlantGetGfClients(): void
    {
        $p = new Plant();
        $p->setNomPlant('Chene pedoncule');

        $this->assertNull($p->getIdPlant());
        $this->assertSame('Chene pedoncule', $p->getNomPlant());
        $this->assertInstanceOf(Collection::class, $p->getGfClients());
        $this->assertTrue($p->getGfClients()->isEmpty());
    }

    public function testPlantGetSetEspece(): void
    {
        $e = new Espece();
        $e->setNomEspece('Chene');

        $p = new Plant();
        $p->setNomPlant('Chene rouge');
        $this->assertNull($p->getEspece());

        $p->setEspece($e);
        $this->assertSame($e, $p->getEspece());

        $p->setEspece(null);
        $this->assertNull($p->getEspece());
    }

    // ── Uv ───────────────────────────────────────────────────────────────────

    public function testUvGetGfHistoClientsEtCommandes(): void
    {
        $e = new Espece();
        $e->setNomEspece('PinPin');

        $u = new Uv();
        $u->setNomUv('UV1')
          ->setNombreGraineParMotte(5)
          ->setNombrePlantParPlateaux(10)
          ->setEspece($e);

        $this->assertNull($u->getIdUv());
        $this->assertSame('UV1', $u->getNomUv());
        $this->assertSame(5, $u->getNombreGraineParMotte());
        $this->assertSame(10, $u->getNombrePlantParPlateaux());
        $this->assertSame($e, $u->getEspece());
        $this->assertInstanceOf(Collection::class, $u->getGfHistoClients());
        $this->assertInstanceOf(Collection::class, $u->getCommandes());
        $this->assertTrue($u->getGfHistoClients()->isEmpty());
        $this->assertTrue($u->getCommandes()->isEmpty());
    }

    // ── GfClient ──────────────────────────────────────────────────────────────

    public function testGfClientGetGfHistoClients(): void
    {
        $client = new Client();
        $client->setNomClient('Test')->setPrenomClient('P');

        $plant = new Plant();
        $plant->setNomPlant('Rosa');

        $g = new GfClient();
        $g->setNumeroLot('LOT-UNIT')->setQuantiteDisponible(50)
          ->setSeuilAlerte(5)->setNomClient('Test')
          ->setClient($client)->setPlant($plant);

        $this->assertNull($g->getIdGfClient());
        $this->assertInstanceOf(Collection::class, $g->getGfHistoClients());
        $this->assertTrue($g->getGfHistoClients()->isEmpty());
        $this->assertInstanceOf(Collection::class, $g->getHistoDepots());
    }

    // ── GfHistoClient ─────────────────────────────────────────────────────────

    public function testGfHistoClientGetIdHistoNullAvantPersistance(): void
    {
        $client = new Client();
        $client->setNomClient('C')->setPrenomClient('P');
        $plant = new Plant();
        $plant->setNomPlant('P');
        $gf = new GfClient();
        $gf->setNumeroLot('L')->setQuantiteDisponible(10)->setSeuilAlerte(1)
           ->setNomClient('C')->setClient($client)->setPlant($plant);

        $espece = new Espece();
        $espece->setNomEspece('E');
        $uv = new Uv();
        $uv->setNomUv('U')->setNombreGraineParMotte(3)->setNombrePlantParPlateaux(6)->setEspece($espece);

        $h = new GfHistoClient();
        $h->setQuantiteSemee(10)
          ->setDateSemis(new \DateTime('today'))
          ->setNbGraineParMotte(3)
          ->setNomUv('U')
          ->setGfClient($gf)
          ->setUv($uv);

        $this->assertNull($h->getIdHisto());
        $this->assertSame(10, $h->getQuantiteSemee());
        $this->assertSame(3, $h->getNbGraineParMotte());
        $this->assertSame('U', $h->getNomUv());
        $this->assertSame($gf, $h->getGfClient());
        $this->assertSame($uv, $h->getUv());
    }
}
