<?php

namespace App\Tests\Functional\Controller;

class MeteoControllerTest extends ApiTestCase
{
    public function testMeteoSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/meteo');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeteoAvecTokenRetourne200ou503(): void
    {
        $this->browser->request('GET', '/api/meteo', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        // 200 si open-meteo.com accessible, 503 sinon
        $this->assertContains($statusCode, [200, 503]);
    }

    public function testMeteoAvecCoordonneesValides(): void
    {
        $this->browser->request('GET', '/api/meteo?lat=48.85&lon=2.35', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 503]);

        if ($statusCode === 200) {
            $data = json_decode($this->browser->getResponse()->getContent(), true);
            $this->assertArrayHasKey('temperature', $data);
            $this->assertArrayHasKey('weathercode', $data);
        }
    }

    public function testMeteoAvecCoordonneesInvalidesFallbackAuDefaut(): void
    {
        // Coordonnées hors limites → fallback sur DEFAULT_LAT/LON
        $this->browser->request('GET', '/api/meteo?lat=999&lon=999', [], [], $this->jsonHeaders($this->employeToken));
        $statusCode = $this->browser->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 503]);
    }
}
