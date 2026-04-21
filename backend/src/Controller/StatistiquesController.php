<?php

namespace App\Controller;

use App\Repository\GfClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/api/statistiques', methods: ['GET'])]
    public function index(GfClientRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $all = $repo->findAll();
        $total = count($all);

        $parStatut = ['en_attente' => 0, 'en_stock' => 0, 'epuise' => 0];
        foreach ($all as $g) {
            $depots = $g->getHistoDepots()->toArray();
            if (!empty($depots)) {
                usort($depots, fn($a, $b) => $b->getIdHistoDepot() - $a->getIdHistoDepot());
                $statut = $depots[0]->getStatut();
                if (isset($parStatut[$statut])) $parStatut[$statut]++;
            } else {
                $parStatut['en_attente']++;
            }
        }

        $conn = $em->getConnection();
        $sql  = "SELECT DATE_FORMAT(date_reception, '%Y-%m') as mois, COUNT(*) as total
                 FROM histo_gf_deposee GROUP BY mois ORDER BY mois ASC";
        $rows = $conn->executeQuery($sql)->fetchAllAssociative();
        $evolutionMensuelle = array_map(fn($r) => ['mois' => $r['mois'], 'total' => (int) $r['total']], $rows);

        return $this->json([
            'total'              => $total,
            'parStatut'          => $parStatut,
            'evolutionMensuelle' => $evolutionMensuelle,
        ]);
    }
}
