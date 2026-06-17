<?php

namespace App\Tests\Unit;

use App\Entity\Utilisateur;
use App\EventListener\LoginSuccessListener;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginSuccessListenerTest extends TestCase
{
    public function testNonUtilisateurIgnoreLeLog(): void
    {
        // Couvre le return; dans onAuthenticationSuccess() quand $user n'est pas Utilisateur
        $fakeUser = new class implements UserInterface {
            public function getRoles(): array { return []; }
            public function getPassword(): ?string { return null; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'external-user'; }
        };

        $event = new AuthenticationSuccessEvent(['token' => 'dummy'], $fakeUser, new Response());

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->never())->method('log');

        $em = $this->createMock(EntityManagerInterface::class);

        $listener = new LoginSuccessListener($logService, $em);
        $listener->onAuthenticationSuccess($event);

        $this->assertTrue(true);
    }
}
