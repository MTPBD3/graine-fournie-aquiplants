<?php

namespace App\Tests\Functional\Controller;

class GfHistoClientControllerTest extends ApiTestCase
{
    public function testHistoClientSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/gf-histo-clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testHistoClientSansParamRetourne400(): void
    {
        $this->browser->request('GET', '/api/gf-histo-clients', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testHistoClientAvecParamRetourne200(): void
    {
        $idEspece = $this->makeEspece('E HistoClient');
        $idPlant  = $this->makePlant('P HistoClient', $idEspece);
        $idClient = $this->makeClient('ClientHistoClient', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-HISTO-CLIENT-TC');

        $this->browser->request('GET', "/api/gf-histo-clients?idGfClient=$idGf", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        // Pas de semis → liste vide
        $this->assertSame([], $data);
    }

    public function testHistoClientAvecSemisRetourneListe(): void
    {
        $idEspece = $this->makeEspece('E HistoClient2');
        $idPlant  = $this->makePlant('P HistoClient2', $idEspece);
        $idUv     = $this->makeUv($idEspece, 'UV HistoClient2');
        $idClient = $this->makeClient('ClientHistoClient2', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-HISTO-CLIENT2-TC', 100, 10);

        // Utiliser le sachet pour créer un enregistrement gf_histo_client
        $this->browser->request(
            'POST', "/api/gf-clients/$idGf/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => $idUv, 'quantiteUtilisee' => 5])
        );
        $this->assertResponseIsSuccessful();

        $this->browser->request('GET', "/api/gf-histo-clients?idGfClient=$idGf", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('dateSemis', $data[0]);

        $this->deleteGfHistoClientByGfClient($idGf);
    }
}
