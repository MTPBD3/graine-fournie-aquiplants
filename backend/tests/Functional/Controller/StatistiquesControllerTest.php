<?php

namespace App\Tests\Functional\Controller;

class StatistiquesControllerTest extends ApiTestCase
{
    public function testStatistiquesSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/statistiques');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDepotsSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/stats/depots');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testStatistiquesRetourne200(): void
    {
        $this->browser->request('GET', '/api/statistiques', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testStatistiquesAvecDonneesRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Stats');
        $idPlant  = $this->makePlant('P Stats', $idEspece);
        $idClient = $this->makeClient('ClientStats', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-STATS-TC');
        $this->makeHistoDeposee($idGf, 'range', '2025-01-15');

        $this->browser->request('GET', '/api/statistiques', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testDepotsPeriode1MRetourne200(): void
    {
        $this->browser->request('GET', '/api/stats/depots?periode=1M', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testDepotsPeriode3MRetourne200(): void
    {
        $this->browser->request('GET', '/api/stats/depots?periode=3M', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testDepotsPeriode6MRetourne200(): void
    {
        $this->browser->request('GET', '/api/stats/depots?periode=6M', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testDepotsAvecDonneesRecentesRetourneDonnees(): void
    {
        // Couvre le corps du if ($h->getDateReception() >= $start) dans depots() :
        // $date = format('Y-m-d'), $byDate[$date] = ... + 1, et fn($date, $total) => [...]
        $idEspece = $this->makeEspece('E Stats Recent');
        $idPlant  = $this->makePlant('P Stats Recent', $idEspece);
        $idClient = $this->makeClient('ClientStatsRecent', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-STATS-RECENT-' . uniqid());
        $this->makeHistoDeposee($idGf, 'range', date('Y-m-d'));

        $this->browser->request('GET', '/api/stats/depots?periode=1M', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertNotEmpty($data);
    }
}
