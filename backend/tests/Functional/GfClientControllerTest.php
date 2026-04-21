<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GfClientControllerTest extends WebTestCase
{
    public function testGetGfClientsSansTokenRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/gf-clients');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetGfClientsAvecTokenInvalidRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/gf-clients', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer token_invalide',
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetAlertesSansTokenRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/alertes');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetStatistiquesSansTokenRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/statistiques');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetEmplacementsSansTokenRetourne401(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/emplacements');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginAvecCredentialsVideRetourne400(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['email' => '', 'password' => '']));
        $this->assertResponseStatusCodeSame(400);
    }
}
