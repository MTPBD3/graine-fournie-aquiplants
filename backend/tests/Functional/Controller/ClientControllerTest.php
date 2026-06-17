<?php

namespace App\Tests\Functional\Controller;

class ClientControllerTest extends ApiTestCase
{
    // ── 401 sans token ────────────────────────────────────────────────────────

    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testShowSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/clients/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/clients', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/clients/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/clients/1');
        $this->assertResponseStatusCodeSame(401);
    }

    // ── GET index ─────────────────────────────────────────────────────────────

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/clients', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testIndexAvecSearchRetourneListe(): void
    {
        $id = $this->makeClient('UniqueClientSearch', 'Prenom');
        $this->browser->request('GET', '/api/clients?search=UniqueClientSearch', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertSame('UniqueClientSearch', $data[0]['nom']);
    }

    // ── GET show ─────────────────────────────────────────────────────────────

    public function testShowIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/clients/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowRetourneClient(): void
    {
        $id = $this->makeClient('ClientShow', 'PrenomShow');
        $this->browser->request('GET', "/api/clients/$id", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('ClientShow', $data['nom']);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateRetourne201(): void
    {
        $this->browser->request(
            'POST', '/api/clients', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nom' => 'NouveauClient', 'prenom' => 'Nouveau'])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('client', 'id_client', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/clients/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nom' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $id = $this->makeClient('AvantUpdate', 'P');
        $this->browser->request(
            'PUT', "/api/clients/$id", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nom' => 'ApresUpdate'])
        );
        $this->assertResponseIsSuccessful();
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/clients/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $id = $this->makeClient('ASupprimer', 'P');
        $this->browser->request('DELETE', "/api/clients/$id", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }
}
