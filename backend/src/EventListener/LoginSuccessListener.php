<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;

class LoginSuccessListener
{
    public function __construct(
        private LogService $logService,
        private EntityManagerInterface $em,
    ) {}

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof Utilisateur) {
            return;
        }

        $this->logService->log(
            $this->em,
            $user,
            'connexion',
            "Connexion de l'utilisateur #" . $user->getId(),
        );
    }
}
