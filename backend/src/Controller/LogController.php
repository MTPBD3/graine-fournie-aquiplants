<?php

namespace App\Controller;

use App\Repository\LogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class LogController extends AbstractController
{
    #[Route('/api/logs', methods: ['GET'])]
    public function index(LogRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $logs = $repo->createQueryBuilder('l')
            ->orderBy('l.dateAction', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn($l) => [
            'id'         => $l->getIdLog(),
            'action'     => $l->getAction(),
            'detail'     => $l->getDetail(),
            'dateAction' => $l->getDateAction()->format('Y-m-d H:i:s'),
            'utilisateur' => [
                'id'     => $l->getUtilisateur()->getId(),
                'nom'    => $l->getUtilisateur()->getNom(),
                'prenom' => $l->getUtilisateur()->getPrenom(),
                'email'  => $l->getUtilisateur()->getEmail(),
            ],
        ], $logs);

        return $this->json($data);
    }
}
