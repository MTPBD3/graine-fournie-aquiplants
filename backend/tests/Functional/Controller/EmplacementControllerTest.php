<?php

namespace App\Tests\Functional\Controller;

class EmplacementControllerTest extends ApiTestCase
{
    // ── 401 sans token ────────────────────────────────────────────────────────

    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/emplacements');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLibresSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/emplacements/libres');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testOccupesSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/emplacements/occupes');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAssignerSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/emplacements/assigner', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    // ── GET libres ────────────────────────────────────────────────────────────

    public function testLibresRetourneListe(): void
    {
        $this->browser->request('GET', '/api/emplacements/libres', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        foreach ($data as $item) {
            $this->assertArrayHasKey('code', $item);
        }
    }

    // ── GET occupes ───────────────────────────────────────────────────────────

    public function testOccupesRetourneListe(): void
    {
        $this->browser->request('GET', '/api/emplacements/occupes', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET index ─────────────────────────────────────────────────────────────

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/emplacements', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET show ─────────────────────────────────────────────────────────────

    public function testShowIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/emplacements/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRetourneEmplacement(): void
    {
        $idGfClient = $this->createGfClientWithEmplacement('A', 2, 'LOT-SHOW-EMPL-TC');
        $idEmpl = $this->getEmplacementId('A', 2);

        $this->browser->request('GET', "/api/emplacements/$idEmpl", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('A-2', $data['code']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/emplacements/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/emplacements/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['lettreEtagere' => 'B'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $idEmpl = $this->makeEmplacement('B', 3);
        $this->browser->request(
            'PUT', "/api/emplacements/$idEmpl", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['lettreEtagere' => 'C', 'numeroEtage' => 4])
        );
        $this->assertResponseIsSuccessful();
    }

    // ── DELETE /{id} ──────────────────────────────────────────────────────────

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/emplacements/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/emplacements/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $idEmpl = $this->makeEmplacement('D', 4);
        $this->browser->request('DELETE', "/api/emplacements/$idEmpl", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }

    // ── POST assigner (statique) ───────────────────────────────────────────────

    public function testAssignerGfClientIntrouvableRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => 999999, 'lettreEtagere' => 'A', 'numeroEtage' => 1])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testAssignerEmplacementInvalideRetourne400(): void
    {
        $idEspece = $this->makeEspece('E Assigner400');
        $idPlant  = $this->makePlant('P Assigner400', $idEspece);
        $idClient = $this->makeClient('ClientAssigner400', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-ASSIGNER-400-TC');

        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'lettreEtagere' => 'Z', 'numeroEtage' => 9])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testAssignerRetourne201(): void
    {
        $idEspece = $this->makeEspece('E Assigner201');
        $idPlant  = $this->makePlant('P Assigner201', $idEspece);
        $idClient = $this->makeClient('ClientAssigner201', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-ASSIGNER-TC-' . uniqid(), 50, 5);

        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'lettreEtagere' => 'A', 'numeroEtage' => 3])
        );
        $this->assertResponseStatusCodeSame(201);
        $data   = json_decode($this->browser->getResponse()->getContent(), true);
        $idEmpl = $data['id'];
        $this->scheduleDelete('histo_gf_deposee', 'id_gf_client', $idGf);
        $this->scheduleDelete('emplacement', 'id_emplacement', $idEmpl);
    }

    public function testAssignerAvecHistoExistantRetourne201(): void
    {
        $idEspece = $this->makeEspece('E AssignerHisto');
        $idPlant  = $this->makePlant('P AssignerHisto', $idEspece);
        $idClient = $this->makeClient('ClientAssignerHisto', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-ASSIGNER-HISTO-' . uniqid(), 50, 5);
        $this->makeHistoDeposee($idGf, 'a_traiter');

        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'lettreEtagere' => 'B', 'numeroEtage' => 2])
        );
        $this->assertResponseStatusCodeSame(201);
        $data   = json_decode($this->browser->getResponse()->getContent(), true);
        $idEmpl = $data['id'];
        $this->scheduleDelete('emplacement', 'id_emplacement', $idEmpl);
    }

    // ── POST /{id}/assigner (dynamique) ───────────────────────────────────────

    public function testAjouterSachetEmplacementIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'POST', '/api/emplacements/999999/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => 1])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAjouterSachetGfClientIntrouvableRetourne400(): void
    {
        $idEmpl = $this->makeEmplacement('C', 1);
        $this->browser->request(
            'POST', "/api/emplacements/$idEmpl/assigner", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => 999999])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testAjouterSachetRetourne200(): void
    {
        $idEspece = $this->makeEspece('E AjouterSachet');
        $idPlant  = $this->makePlant('P AjouterSachet', $idEspece);
        $idClient = $this->makeClient('ClientAjouterSachet', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-AJOUTER-SACHET-' . uniqid(), 50, 5);
        $idEmpl   = $this->makeEmplacement('D', 1);

        $this->browser->request(
            'POST', "/api/emplacements/$idEmpl/assigner", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf])
        );
        $this->assertResponseIsSuccessful();
        $this->scheduleDelete('histo_gf_deposee', 'id_gf_client', $idGf);
    }

    // ── DELETE /{id}/liberer/{idGfClient} ─────────────────────────────────────

    public function testLibererSachetEmplacementIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/emplacements/999999/liberer/1', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLibererSachetGfClientIntrouvableRetourne404(): void
    {
        $idEmpl = $this->makeEmplacement('A', 4);
        $this->browser->request("DELETE", "/api/emplacements/$idEmpl/liberer/999999", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLibererSachetRetourne204(): void
    {
        $idGfClient = $this->createGfClientWithEmplacement('C', 3, 'LOT-LIBERER-SACHET-TC-' . uniqid());
        $idEmpl     = $this->getEmplacementId('C', 3);

        $this->browser->request("DELETE", "/api/emplacements/$idEmpl/liberer/$idGfClient", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }

    // ── DELETE /{id}/liberer ───────────────────────────────────────────────────

    public function testLibererIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/emplacements/999999/liberer', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLibererRetourne204(): void
    {
        $idGfClient = $this->createGfClientWithEmplacement('D', 2, 'LOT-LIBERER-TOUT-TC-' . uniqid());
        $idEmpl     = $this->getEmplacementId('D', 2);

        $this->browser->request("DELETE", "/api/emplacements/$idEmpl/liberer", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }

    // ── Tests supplémentaires pour couverture ─────────────────────────────────

    public function testAjouterSachetAvecHistoExistantRetourne200(): void
    {
        // Couvre if (!empty($depots)) dans ajouterSachet() : usort + $depots[0]->setStatut()
        $idEspece = $this->makeEspece('E AjouterHisto');
        $idPlant  = $this->makePlant('P AjouterHisto', $idEspece);
        $idClient = $this->makeClient('ClientAjouterHisto', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-AJOUTER-HISTO-' . uniqid(), 50, 5);
        $this->makeHistoDeposee($idGf, 'a_traiter');
        $idEmpl = $this->makeEmplacement('A', 1);

        $this->browser->request(
            'POST', "/api/emplacements/$idEmpl/assigner", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf])
        );
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteAvecSachetsRetourne204(): void
    {
        // Couvre $sachet->setEmplacement(null) dans la boucle foreach de delete()
        $idGfClient = $this->createGfClientWithEmplacement('B', 1, 'LOT-DEL-SACHETS-' . uniqid());
        $idEmpl     = $this->getEmplacementId('B', 1);

        $this->browser->request('DELETE', "/api/emplacements/$idEmpl", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function createGfClientWithEmplacement(string $lettre, int $etage, string $lot): int
    {
        $idEspece = $this->makeEspece("E $lettre$etage");
        $idPlant  = $this->makePlant("P $lettre$etage", $idEspece);
        $idClient = $this->makeClient("Client$lettre$etage", 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, $lot, 50, 5);

        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'lettreEtagere' => $lettre, 'numeroEtage' => $etage])
        );
        $this->scheduleDelete('histo_gf_deposee', 'id_gf_client', $idGf);
        $data   = json_decode($this->browser->getResponse()->getContent(), true);
        $idEmpl = $data['id'] ?? 0;
        $this->scheduleDelete('emplacement', 'id_emplacement', $idEmpl);

        return $idGf;
    }

    private function getEmplacementId(string $lettre, int $etage): int
    {
        $conn = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection();
        $id   = $conn->fetchOne(
            'SELECT id_emplacement FROM emplacement WHERE lettre_etagere = ? AND numero_etage = ?',
            [$lettre, $etage]
        );
        return (int) $id;
    }
}
