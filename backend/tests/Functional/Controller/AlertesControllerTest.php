<?php

namespace App\Tests\Functional\Controller;

class AlertesControllerTest extends ApiTestCase
{
    public function testAlertesSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/alertes');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAlertesAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request('GET', '/api/alertes', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAlertesAvecTokenAdminRetourne200(): void
    {
        $this->browser->request('GET', '/api/alertes', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testAlertesAvecDonneesAnciennesRetourneResultats(): void
    {
        $idEspece = $this->makeEspece('E Alertes Data');
        $idPlant  = $this->makePlant('P Alertes Data', $idEspece);
        $idClient = $this->makeClient('ClientAlertesData', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-ALERTES-DATA-TC');
        // Dépôt en attente depuis 5 jours → doit apparaître dans les alertes
        $this->makeHistoDeposee($idGf, 'a_traiter', '-5 days');

        $this->browser->request('GET', '/api/alertes', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        // Au moins un résultat avec les données créées
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('joursAttente', $data[0]);
        $this->assertArrayHasKey('numeroLot', $data[0]);
    }
}
