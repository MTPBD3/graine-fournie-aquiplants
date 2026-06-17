<?php

namespace App\Tests\Functional\Controller;

class HistoGfDeposeeControllerTest extends ApiTestCase
{
    // ── 401 sans token ────────────────────────────────────────────────────────

    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/histo-gf-deposees');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/histo-gf-deposees/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/histo-gf-deposees', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/histo-gf-deposees/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/histo-gf-deposees/1');
        $this->assertResponseStatusCodeSame(401);
    }

    // ── GET index ─────────────────────────────────────────────────────────────

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/histo-gf-deposees', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET show ─────────────────────────────────────────────────────────────

    public function testShowIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/histo-gf-deposees/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRetourneHisto(): void
    {
        $idEspece  = $this->makeEspece('E Show Histo');
        $idPlant   = $this->makePlant('P Show Histo', $idEspece);
        $idClient  = $this->makeClient('ClientShowHisto', 'P');
        $idGf      = $this->makeGfClient($idClient, $idPlant, 'LOT-SHOW-HISTO-TC');
        $idHisto   = $this->makeHistoDeposee($idGf);

        $this->browser->request('GET', "/api/histo-gf-deposees/$idHisto", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('statut', $data);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateGfClientIntrouvableRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/histo-gf-deposees', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => 999999, 'quantiteDeposee' => 10, 'dateReception' => '2024-01-01'])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateStatutInvalideRetourne422(): void
    {
        $idEspece = $this->makeEspece('E Crea Histo Inv');
        $idPlant  = $this->makePlant('P Crea Histo Inv', $idEspece);
        $idClient = $this->makeClient('ClientCreaHistoInv', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-CREA-HISTOINV-TC');

        $this->browser->request(
            'POST', '/api/histo-gf-deposees', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'quantiteDeposee' => 20, 'dateReception' => '2024-06-01', 'statut' => 'invalide'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateRetourne201(): void
    {
        $idEspece = $this->makeEspece('E Crea Histo');
        $idPlant  = $this->makePlant('P Crea Histo', $idEspece);
        $idClient = $this->makeClient('ClientCreaHisto', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-CREA-HISTO-TC');

        $this->browser->request(
            'POST', '/api/histo-gf-deposees', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'quantiteDeposee' => 20, 'dateReception' => '2024-06-01', 'statut' => 'a_traiter'])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('histo_gf_deposee', 'id_histo_depot', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateStatutInvalideRetourne422(): void
    {
        $idEspece = $this->makeEspece('E Upd Histo Inv');
        $idPlant  = $this->makePlant('P Upd Histo Inv', $idEspece);
        $idClient = $this->makeClient('ClientUpdHistoInv', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-UPD-HISTOINV-TC');
        $idHisto  = $this->makeHistoDeposee($idGf, 'a_traiter');

        $this->browser->request(
            'PUT', "/api/histo-gf-deposees/$idHisto", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['statut' => 'invalide'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/histo-gf-deposees/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['statut' => 'range'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Upd Histo');
        $idPlant  = $this->makePlant('P Upd Histo', $idEspece);
        $idClient = $this->makeClient('ClientUpdHisto', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-UPD-HISTO-TC');
        $idHisto  = $this->makeHistoDeposee($idGf, 'a_traiter');

        $this->browser->request(
            'PUT', "/api/histo-gf-deposees/$idHisto", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['statut' => 'range', 'note' => 'test note'])
        );
        $this->assertResponseIsSuccessful();
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/histo-gf-deposees/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $idEspece = $this->makeEspece('E Del Histo');
        $idPlant  = $this->makePlant('P Del Histo', $idEspece);
        $idClient = $this->makeClient('ClientDelHisto', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-DEL-HISTO-TC');
        $idHisto  = $this->makeHistoDeposee($idGf);

        $this->browser->request('DELETE', "/api/histo-gf-deposees/$idHisto", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }
}
