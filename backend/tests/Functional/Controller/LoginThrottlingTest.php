<?php

namespace App\Tests\Functional\Controller;

class LoginThrottlingTest extends ApiTestCase
{
    public function testLoginBloqueApres5TentativesRetourne429(): void
    {
        $email = 'throttle-' . uniqid() . '@test.aquiplants';

        for ($i = 0; $i < 5; $i++) {
            $this->browser->request(
                'POST', '/api/login',
                [], [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['email' => $email, 'password' => 'wrong-password-' . $i])
            );
        }

        $this->browser->request(
            'POST', '/api/login',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'wrong-again'])
        );
        $this->assertResponseStatusCodeSame(429);
    }

    public function testLoginMotDePasseVideRetourne401AvecMessage(): void
    {
        // email non-vide + password vide → JsonLoginAuthenticator lève BadRequestHttpException
        // "must be a non-empty string" → LoginExceptionListener::setResponse() → 401 JSON
        $this->browser->request(
            'POST', '/api/login',
            [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'quelconque@test.fr', 'password' => ''])
        );
        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame('Invalid credentials.', $data['message'] ?? '');
    }
}
