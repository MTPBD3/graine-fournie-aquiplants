<?php

namespace App\Tests\Functional\Controller;

class PlantControllerTest extends ApiTestCase
{
    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/plants');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/plants', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET show ─────────────────────────────────────────────────────────────

    public function testShowSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/plants/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/plants/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRetournePlant(): void
    {
        $idEspece = $this->makeEspece('Espece Pour Plant');
        $idPlant  = $this->makePlant('Plant Show Test', $idEspece);
        $this->browser->request('GET', "/api/plants/$idPlant", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('Plant Show Test', $data['nomPlant']);
        $this->assertArrayHasKey('nomEspece', $data);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/plants', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateNomVideRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/plants', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomPlant' => ''])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateRetourne201(): void
    {
        $idEspece = $this->makeEspece('Espece Crea Plant');
        $this->browser->request(
            'POST', '/api/plants', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomPlant' => 'Nouveau Plant TC', 'idEspece' => $idEspece])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('plant', 'id_plant', $data['id']);
    }

    public function testCreateSansEspeceRetourne201(): void
    {
        $this->browser->request(
            'POST', '/api/plants', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomPlant' => 'Plant Sans Espece TC'])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->scheduleDelete('plant', 'id_plant', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/plants/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/plants/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomPlant' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $id = $this->makePlant('Avant Plant Update');
        $this->browser->request(
            'PUT', "/api/plants/$id", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomPlant' => 'Apres Plant Update'])
        );
        $this->assertResponseIsSuccessful();
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/plants/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/plants/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $id = $this->makePlant('Plant A Supprimer');
        $this->browser->request('DELETE', "/api/plants/$id", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }

    public function testUpdateAvecEspeceRetourne200(): void
    {
        // Couvre la branche if (isset($data['idEspece'])) dans update()
        $idEspece = $this->makeEspece('E Update Plant');
        $idPlant  = $this->makePlant('Plant Avant Espece');

        $this->browser->request(
            'PUT', "/api/plants/$idPlant", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomPlant' => 'Plant Apres Espece', 'idEspece' => $idEspece])
        );
        $this->assertResponseIsSuccessful();
    }
}
