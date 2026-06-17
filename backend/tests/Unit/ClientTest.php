<?php

namespace App\Tests\Unit;

use App\Entity\Client;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function testGetNomEtPrenom(): void
    {
        $c = new Client();
        $c->setNomClient('Dupont')->setPrenomClient('Marie');

        $this->assertSame('Dupont', $c->getNomClient());
        $this->assertSame('Marie', $c->getPrenomClient());
    }

    public function testPrenomClientNullable(): void
    {
        $c = new Client();
        $c->setNomClient('Sans Prenom')->setPrenomClient(null);
        $this->assertNull($c->getPrenomClient());
    }

    public function testGetIdClientNullAvantPersistance(): void
    {
        $c = new Client();
        $c->setNomClient('Test');
        $this->assertNull($c->getIdClient());
    }

    public function testGetGfClientsRetourneCollection(): void
    {
        $c = new Client();
        $c->setNomClient('Test');
        $this->assertInstanceOf(Collection::class, $c->getGfClients());
        $this->assertTrue($c->getGfClients()->isEmpty());
    }

    public function testGetCommandesRetourneCollection(): void
    {
        $c = new Client();
        $c->setNomClient('Test');
        $this->assertInstanceOf(Collection::class, $c->getCommandes());
        $this->assertTrue($c->getCommandes()->isEmpty());
    }

    public function testGetEmplacementsRetourneCollection(): void
    {
        $c = new Client();
        $c->setNomClient('Test');
        $this->assertInstanceOf(Collection::class, $c->getEmplacements());
        $this->assertTrue($c->getEmplacements()->isEmpty());
    }
}
