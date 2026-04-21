<?php

namespace App\Controller;

use App\Repository\LogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logs')]
class LogController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(LogRepository $repo): JsonResponse
    {
        $logs = $repo->findBy([], ['dateAction' => 'DESC'], 200);
        return $this->json(array_map(fn($l) => [
            'id'          => $l->getIdLog(),
            'action'      => $l->getAction(),
            'detail'      => $l->getDetail(),
            'dateAction'  => $l->getDateAction()->format('Y-m-d H:i:s'),
            'utilisateur' => [
                'nom'    => $l->getUtilisateur()->getNom(),
                'prenom' => $l->getUtilisateur()->getPrenom(),
            ],
        ], $logs));
    }
}
