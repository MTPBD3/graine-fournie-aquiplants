<?php

namespace App\Tests\Functional\Controller;

class UvControllerTest extends ApiTestCase
{
    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/uvs');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testIndexRetourneListe(): void
    {
        $this->browser->request('GET', '/api/uvs', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateSansTokenRetourne401(): void
    {
        $this->browser->request('POST', '/api/uvs', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateChampsManquantsRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/uvs', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => ''])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateGraineszeroRetourne400(): void
    {
        $idEspece = $this->makeEspece('Espece UV400');
        $this->browser->request(
            'POST', '/api/uvs', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => 'UV TC', 'nombreGraineParMotte' => 0, 'nombrePlantParPlateaux' => 5, 'idEspece' => $idEspece])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateEspeceIntrouvableRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/uvs', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => 'UV TC', 'nombreGraineParMotte' => 5, 'nombrePlantParPlateaux' => 10, 'idEspece' => 999999])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateRetourne201(): void
    {
        $idEspece = $this->makeEspece('Espece Crea UV');
        $this->browser->request(
            'POST', '/api/uvs', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => 'UV TC Crea', 'nombreGraineParMotte' => 5, 'nombrePlantParPlateaux' => 10, 'idEspece' => $idEspece])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('uv', 'id_uv', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateSansTokenRetourne401(): void
    {
        $this->browser->request('PUT', '/api/uvs/1', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/uvs/999999', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRetourne200(): void
    {
        $idEspece = $this->makeEspece('Espece UV Update');
        $idUv     = $this->makeUv($idEspece, 'UV Avant Update');
        $this->browser->request(
            'PUT', "/api/uvs/$idUv", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => 'UV Apres Update'])
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('UV Apres Update', $data['nomUv']);
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteSansTokenRetourne401(): void
    {
        $this->browser->request('DELETE', '/api/uvs/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/uvs/999999', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $idEspece = $this->makeEspece('Espece UV Del');
        $idUv     = $this->makeUv($idEspece, 'UV A Supprimer');
        $this->browser->request('DELETE', "/api/uvs/$idUv", [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(204);
    }

    public function testUpdateAvecIdEspeceRetourne200(): void
    {
        // Couvre la branche if (isset($data['idEspece'])) dans update()
        $idEspece1 = $this->makeEspece('Espece UV UpdRel1');
        $idEspece2 = $this->makeEspece('Espece UV UpdRel2');
        $idUv      = $this->makeUv($idEspece1, 'UV UpdRel');

        $this->browser->request(
            'PUT', "/api/uvs/$idUv", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nomUv' => 'UV UpdRel Modif', 'idEspece' => $idEspece2])
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('Espece UV UpdRel2', $data['espece']['nomEspece']);
    }
}
