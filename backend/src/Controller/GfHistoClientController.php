<?php

namespace App\Controller;

use App\Repository\GfHistoClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GfHistoClientController extends AbstractController
{
    #[Route('/api/gf-histo-clients', methods: ['GET'])]
    public function index(Request $request, GfHistoClientRepository $repo): JsonResponse
    {
        $idGfClient = $request->query->getInt('idGfClient', 0);

        if ($idGfClient === 0) {
            return $this->json(['message' => 'Paramètre idGfClient requis'], 400);
        }

        $items = $repo->createQueryBuilder('h')
            ->where('h.gfClient = :id')
            ->setParameter('id', $idGfClient)
            ->orderBy('h.dateSemis', 'DESC')
            ->getQuery()
            ->getResult();

        $data = array_map(fn($h) => [
            'id'               => $h->getIdHisto(),
            'dateSemis'        => $h->getDateSemis()->format('Y-m-d'),
            'quantiteSemee'    => $h->getQuantiteSemee(),
            'nomUv'            => $h->getNomUv(),
            'nbGraineParMotte' => $h->getNbGraineParMotte(),
            'uv' => $h->getUv() !== null ? [
                'id'    => $h->getUv()->getIdUv(),
                'nomUv' => $h->getUv()->getNomUv(),
            ] : null,
        ], $items);

        return $this->json($data);
    }
}
