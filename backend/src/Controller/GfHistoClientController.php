<?php

namespace App\Controller;

use App\Repository\GfHistoClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/gf-histo-clients')]
class GfHistoClientController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(Request $request, GfHistoClientRepository $repo): JsonResponse
    {
        $idGfClient = $request->query->getInt('idGfClient');
        $items = $idGfClient
            ? $repo->findBy(['gfClient' => $idGfClient], ['dateSemis' => 'DESC'])
            : $repo->findBy([], ['dateSemis' => 'DESC'], 100);

        return $this->json(array_map(fn($h) => [
            'id'              => $h->getIdHisto(),
            'quantiteSemee'   => $h->getQuantiteSemee(),
            'dateSemis'       => $h->getDateSemis()->format('Y-m-d'),
            'nbGraineParMotte'=> $h->getNbGraineParMotte(),
            'nomUv'           => $h->getNomUv(),
        ], $items));
    }
}
