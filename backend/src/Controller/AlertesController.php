<?php

namespace App\Controller;

use App\Repository\HistoGfDeposeeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AlertesController extends AbstractController
{
    /** Nombre de jours d'attente au-delà duquel un sachet est considéré en alerte. */
    private const DELAI_JOURS = 3;

    #[Route('/api/alertes', methods: ['GET'])]
    public function index(HistoGfDeposeeRepository $repo): JsonResponse
    {
        $limite = new \DateTime('-' . self::DELAI_JOURS . ' days');
        $items  = $repo->findEnAttenteSince($limite);

        $today = new \DateTime('today');

        $data = array_map(function ($h) use ($today) {
            $dateReception = $h->getDateReception();
            $joursAttente  = (int) $today->diff($dateReception)->days;

            $gf     = $h->getGfClient();
            $client = $gf->getClient();

            return [
                'id'              => $h->getIdHistoDepot(),
                'dateReception'   => $dateReception->format('Y-m-d'),
                'joursAttente'    => $joursAttente,
                'quantiteDeposee' => $h->getQuantiteDeposee(),
                'statut'          => $h->getStatut(),
                'numeroLot'       => $gf->getNumeroLot(),
                'nomClient'       => $gf->getNomClient(),
                'plant'           => [
                    'nomPlant' => $gf->getPlant()->getNomPlant(),
                ],
                'client'          => [
                    'nom'    => $client->getNomClient(),
                    'prenom' => $client->getPrenomClient(),
                ],
            ];
        }, $items);

        return $this->json($data);
    }
}
