<?php

namespace App\Tests\Functional\Controller;

class GfClientCrudTest extends ApiTestCase
{
    // ── 401 sans token ────────────────────────────────────────────────────────

    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/gf-clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/gf-clients/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/gf-clients', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/gf-clients/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/gf-clients/1');
        $this->assertResponseStatusCodeSame(401);
    }

    // ── GET index ─────────────────────────────────────────────────────────────

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/gf-clients', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testIndexAvecSearchRetourneListe(): void
    {
        $idEspece  = $this->makeEspece('E Search GF');
        $idPlant   = $this->makePlant('P Search GF', $idEspece);
        $idClient  = $this->makeClient('ClientSearchGF', 'P');
        $idGfClient = $this->makeGfClient($idClient, $idPlant, 'LOT-SEARCH-GF-TC');

        $this->browser->request('GET', '/api/gf-clients?search=LOT-SEARCH-GF-TC', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET alertes ───────────────────────────────────────────────────────────

    public function testAlertesSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/gf-clients/alertes');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAlertesRetourneListe(): void
    {
        $idEspece   = $this->makeEspece('E Alertes GF');
        $idPlant    = $this->makePlant('P Alertes GF', $idEspece);
        $idClient   = $this->makeClient('ClientAlertesGF', 'P');
        // seuil = 50, qte = 10 → en alerte
        $this->makeGfClient($idClient, $idPlant, 'LOT-ALERTES-TC', 10, 50);

        $this->browser->request('GET', '/api/gf-clients/alertes', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    // ── GET sachets-compatibles ───────────────────────────────────────────────

    public function testSachetsCompatiblesSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/gf-clients/1/sachets-compatibles');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testSachetsCompatiblesIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/gf-clients/999999/sachets-compatibles', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testSachetsCompatiblesRetourneListe(): void
    {
        $idEspece  = $this->makeEspece('E Compat GF');
        $idPlant   = $this->makePlant('P Compat GF', $idEspece);
        $idClient  = $this->makeClient('ClientCompatGF', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-COMPAT-TC');

        $this->browser->request('GET', "/api/gf-clients/$idGf/sachets-compatibles", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET show ─────────────────────────────────────────────────────────────

    public function testShowIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/gf-clients/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRetourneGfClient(): void
    {
        $idEspece  = $this->makeEspece('E Show GF');
        $idPlant   = $this->makePlant('P Show GF', $idEspece);
        $idClient  = $this->makeClient('ClientShowGF', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-SHOW-TC');

        $this->browser->request('GET', "/api/gf-clients/$idGf", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('LOT-SHOW-TC', $data['numeroLot']);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateLotVideRetourne400(): void
    {
        $idClient = $this->makeClient('ClientCreaGF', 'P');
        $idPlant  = $this->makePlant('PlantCreaGF');
        $this->browser->request(
            'POST', '/api/gf-clients', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['numeroLot' => '', 'quantiteDisponible' => 10, 'idClient' => $idClient, 'idPlant' => $idPlant])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateQuantiteInvalideRetourne400(): void
    {
        $idClient = $this->makeClient('ClientCreaGF2', 'P');
        $idPlant  = $this->makePlant('PlantCreaGF2');
        $this->browser->request(
            'POST', '/api/gf-clients', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['numeroLot' => 'LOT-TC', 'quantiteDisponible' => -5, 'idClient' => $idClient, 'idPlant' => $idPlant])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateClientIntrouvableRetourne400(): void
    {
        $idPlant = $this->makePlant('PlantCreaGF3');
        $this->browser->request(
            'POST', '/api/gf-clients', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['numeroLot' => 'LOT-TC', 'quantiteDisponible' => 10, 'idClient' => 999999, 'idPlant' => $idPlant])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateRetourne201(): void
    {
        $idEspece = $this->makeEspece('E Crea GF');
        $idPlant  = $this->makePlant('P Crea GF', $idEspece);
        $idClient = $this->makeClient('ClientCreaGF3', 'P');
        $this->browser->request(
            'POST', '/api/gf-clients', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode([
                'numeroLot'          => 'LOT-CREA-TC-' . uniqid(),
                'quantiteDisponible' => 100,
                'seuilAlerte'        => 10,
                'nomClient'          => 'ClientCreaGF3',
                'idClient'           => $idClient,
                'idPlant'            => $idPlant,
            ])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('gf_client', 'id_gf_client', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/gf-clients/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['numeroLot' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Upd GF');
        $idPlant  = $this->makePlant('P Upd GF', $idEspece);
        $idClient = $this->makeClient('ClientUpdGF', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-UPD-TC');
        $this->browser->request(
            'PUT', "/api/gf-clients/$idGf", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['numeroLot' => 'LOT-UPD-APRES', 'quantiteDisponible' => 50, 'seuilAlerte' => 5])
        );
        $this->assertResponseIsSuccessful();
    }

    public function testUpdateQuantiteInvalideRetourne400(): void
    {
        $idEspece = $this->makeEspece('E Upd QteInv');
        $idPlant  = $this->makePlant('P Upd QteInv', $idEspece);
        $idClient = $this->makeClient('ClientUpdQteInv', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-UPD-QTEINV-TC');
        $this->browser->request(
            'PUT', "/api/gf-clients/$idGf", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['quantiteDisponible' => -1])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateSeuilAlerteInvalideRetourne400(): void
    {
        $idEspece = $this->makeEspece('E Upd SeuilInv');
        $idPlant  = $this->makePlant('P Upd SeuilInv', $idEspece);
        $idClient = $this->makeClient('ClientUpdSeuilInv', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-UPD-SEUIL-TC');
        $this->browser->request(
            'PUT', "/api/gf-clients/$idGf", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['seuilAlerte' => -5])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/gf-clients/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Del GF');
        $idPlant  = $this->makePlant('P Del GF', $idEspece);
        $idClient = $this->makeClient('ClientDelGF', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-DEL-TC');
        $this->browser->request('DELETE', "/api/gf-clients/$idGf", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
    }

    // ── POST utiliser ─────────────────────────────────────────────────────────

    public function testUtiliserSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/gf-clients/1/utiliser', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUtiliserIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'POST', '/api/gf-clients/999999/utiliser', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => 1, 'quantiteUtilisee' => 5])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUtiliserUvIntrouvableRetourne400(): void
    {
        $idEspece = $this->makeEspece('E Util GF');
        $idPlant  = $this->makePlant('P Util GF', $idEspece);
        $idClient = $this->makeClient('ClientUtilGF', 'P');
        $idGf = $this->makeGfClient($idClient, $idPlant, 'LOT-UTIL-TC', 100, 10);
        $this->browser->request(
            'POST', "/api/gf-clients/$idGf/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => 999999, 'quantiteUtilisee' => 5])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testUtiliserRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Util2 GF');
        $idPlant  = $this->makePlant('P Util2 GF', $idEspece);
        $idClient = $this->makeClient('ClientUtil2GF', 'P');
        $idGf  = $this->makeGfClient($idClient, $idPlant, 'LOT-UTIL2-TC', 100, 10);
        $idUv  = $this->makeUv($idEspece, 'UV Util2 TC');

        $this->browser->request(
            'POST', "/api/gf-clients/$idGf/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => $idUv, 'quantiteUtilisee' => 5])
        );
        $this->assertResponseIsSuccessful();

        $this->deleteGfHistoClientByGfClient($idGf);
    }

    // ── Branches non couvertes : getLatestHistoDepot + serialize ─────────────

    public function testShowAvecHistoriqueRetourneStatutEtHistoDepotId(): void
    {
        $idEspece = $this->makeEspece('E ShowHisto');
        $idPlant  = $this->makePlant('P ShowHisto', $idEspece);
        $idClient = $this->makeClient('ClientShowHisto', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-SHOWHISTO-TC');
        // Crée un historique → getLatestHistoDepot retourne un objet non-null
        $this->makeHistoDeposee($idGf, 'range', '2025-03-10');

        $this->browser->request('GET', "/api/gf-clients/$idGf", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('range', $data['statut']);
        $this->assertNotNull($data['histoDepotId']);
    }

    public function testShowAvecTokenAdminRetourne200(): void
    {
        $idEspece = $this->makeEspece('E ShowAdmin');
        $idPlant  = $this->makePlant('P ShowAdmin', $idEspece);
        $idClient = $this->makeClient('ClientShowAdmin', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-SHOWADMIN-TC');

        $this->browser->request('GET', "/api/gf-clients/$idGf", [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('LOT-SHOWADMIN-TC', $data['numeroLot']);
    }

    // ── Branches update avec relations ────────────────────────────────────────

    public function testUpdateAvecIdClientEtIdPlant(): void
    {
        $idEspece  = $this->makeEspece('E UpdRel');
        $idPlant1  = $this->makePlant('P UpdRel1', $idEspece);
        $idPlant2  = $this->makePlant('P UpdRel2', $idEspece);
        $idClient1 = $this->makeClient('ClientUpdRel1', 'P');
        $idClient2 = $this->makeClient('ClientUpdRel2', 'P');
        $idGf      = $this->makeGfClient($idClient1, $idPlant1, 'LOT-UPDREL-TC');

        $this->browser->request(
            'PUT', "/api/gf-clients/$idGf", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idClient' => $idClient2, 'idPlant' => $idPlant2])
        );
        $this->assertResponseIsSuccessful();
    }

    // ── sachetsCompatibles avec sachet range (couvre return true + emplacement) ─

    public function testSachetsCompatiblesAvecSachetRangeRetourneListe(): void
    {
        $idEspece  = $this->makeEspece('E Compat Range');
        $idPlant   = $this->makePlant('P Compat Range', $idEspece);
        $idClient  = $this->makeClient('ClientCompatRange', 'P');
        $idGfMain  = $this->makeGfClient($idClient, $idPlant, 'LOT-COMPAT-MAIN-' . uniqid());
        $idGfComp  = $this->makeGfClient($idClient, $idPlant, 'LOT-COMPAT-COMP-' . uniqid());

        // Assigne un emplacement au sachet compatible → couvre la branche emplacement != null
        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGfComp, 'lettreEtagere' => 'B', 'numeroEtage' => 2])
        );
        $emplData = json_decode($this->browser->getResponse()->getContent(), true);
        if (isset($emplData['id'])) {
            $this->scheduleDelete('emplacement', 'id_emplacement', $emplData['id']);
        }

        // Donne le statut 'range' au sachet compatible
        $this->makeHistoDeposee($idGfComp, 'range', '2025-06-01');

        $this->browser->request(
            'GET', "/api/gf-clients/$idGfMain/sachets-compatibles", [], [],
            $this->jsonHeaders($this->employeToken)
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertSame($idGfComp, $data[0]['id']);
    }
}
