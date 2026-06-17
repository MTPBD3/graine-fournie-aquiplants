<?php

namespace App\Tests\Functional\Controller;

class LogControllerTest extends ApiTestCase
{
    public function testLogsSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/logs');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLogsAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request('GET', '/api/logs', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLogsAvecTokenAdminRetourne200(): void
    {
        $this->browser->request('GET', '/api/logs', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }
}
