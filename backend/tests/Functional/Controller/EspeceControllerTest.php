<?php

namespace App\Tests\Functional\Controller;

class EspeceControllerTest extends ApiTestCase
{
    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/especes');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/especes', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── /uvs ─────────────────────────────────────────────────────────────────

    public function testUvsSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/especes/1/uvs');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUvsIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/especes/999999/uvs', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUvsRetourneListe(): void
    {
        $idEspece = $this->makeEspece('Espece UV Test');
        $this->makeUv($idEspece, 'UV1 Test');
        $this->browser->request('GET', "/api/especes/$idEspece/uvs", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/especes', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateNomVideRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/especes', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomEspece' => '   '])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateRetourne201(): void
    {
        $this->browser->request(
            'POST', '/api/especes', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomEspece' => 'Nouvelle Espece TC'])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('espece', 'id_espece', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/especes/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/especes/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomEspece' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $id = $this->makeEspece('Avant Espece Update');
        $this->browser->request(
            'PUT', "/api/especes/$id", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomEspece' => 'Apres Espece Update'])
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('Apres Espece Update', $data['nomEspece']);
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/especes/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/especes/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $id = $this->makeEspece('Espece A Supprimer');
        $this->browser->request('DELETE', "/api/especes/$id", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }
}
