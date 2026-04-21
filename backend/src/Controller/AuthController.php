<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        return $this->json([
            'id'     => $user->getId(),
            'email'  => $user->getEmail(),
            'nom'    => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'role'   => $user->getRole(),
        ]);
    }
}