<?php

namespace App\Tests\Functional\Controller;

class AuthMeControllerTest extends ApiTestCase
{
    public function testMeSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeAvecTokenInvalidRetourne401(): void
    {
        $this->browser->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid.token.here',
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeAvecTokenEmployeRetourne200(): void
    {
        $this->browser->request('GET', '/api/me', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('email', $data);
        $this->assertSame(self::EMPLOYE_EMAIL, $data['email']);
        $this->assertArrayHasKey('role', $data);
    }

    public function testMeAvecTokenAdminRetourne200(): void
    {
        $this->browser->request('GET', '/api/me', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame(self::ADMIN_EMAIL, $data['email']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('nom', $data);
        $this->assertArrayHasKey('prenom', $data);
    }
}
