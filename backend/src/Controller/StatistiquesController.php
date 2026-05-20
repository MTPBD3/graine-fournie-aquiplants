<?php

namespace App\Controller;

use App\Repository\HistoGfDeposeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/api/statistiques', methods: ['GET'])]
    public function index(HistoGfDeposeeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $all = $repo->findAll();

        // Répartition par statut
        $parStatut = ['a_traiter' => 0, 'range' => 0];
        foreach ($all as $h) {
            $s = $h->getStatut();
            if (isset($parStatut[$s])) {
                $parStatut[$s]++;
            }
        }

        // Évolution mensuelle — requête SQL groupée par mois (format YYYY-MM)
        $conn = $em->getConnection();
        $sql  = "SELECT DATE_FORMAT(date_reception, '%Y-%m') as mois, COUNT(*) as total
                 FROM histo_gf_deposee
                 GROUP BY mois
                 ORDER BY mois ASC";
        $evolutionMensuelle = array_map(
            fn($row) => ['mois' => $row['mois'], 'total' => (int) $row['total']],
            $conn->executeQuery($sql)->fetchAllAssociative()
        );

        // Indicateurs agrégés via PHP (entrees/sorties pour les 12 derniers mois)
        $moisMap = [];
        foreach ($all as $h) {
            $mois = $h->getDateReception()->format('M Y');
            if (!isset($moisMap[$mois])) {
                $moisMap[$mois] = ['entrees' => 0, 'sorties' => 0];
            }
            if ($h->getStatut() === 'range') {
                $moisMap[$mois]['entrees']++;
            }
        }
        uksort($moisMap, fn($a, $b) => strtotime($a) - strtotime($b));
        $evolution12  = array_values(array_slice($moisMap, -12));
        $totalEntrees = array_sum(array_column($evolution12, 'entrees'));
        $totalSorties = array_sum(array_column($evolution12, 'sorties'));

        // Répartition par plante (top 6)
        $categories = [];
        foreach ($all as $h) {
            $nom = $h->getGfClient()->getPlant()->getNomPlant();
            if (!isset($categories[$nom])) {
                $categories[$nom] = ['name' => $nom, 'value' => 0];
            }
            $categories[$nom]['value']++;
        }
        arsort($categories);
        $topCategories = array_values(array_slice($categories, 0, 6));
        $topNom = !empty($topCategories) ? $topCategories[0]['name'] : null;

        // Pic d'activité
        $picMois = null;
        $picMax  = 0;
        foreach ($moisMap as $mois => $d) {
            $t = $d['entrees'] + $d['sorties'];
            if ($t > $picMax) { $picMax = $t; $picMois = $mois; }
        }

        return $this->json([
            'parStatut'          => $parStatut,
            'evolutionMensuelle' => $evolutionMensuelle,
            'categories'         => $topCategories,
            'totalEntrees'       => $totalEntrees,
            'totalSorties'       => $totalSorties,
            'solde'              => ($totalEntrees - $totalSorties >= 0 ? '+' : '') . ($totalEntrees - $totalSorties),
            'categorieTop'       => $topNom,
            'picActivite'        => $picMois,
        ]);
    }

    #[Route('/api/stats/depots', methods: ['GET'])]
    public function depots(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $periode = $request->query->get('periode', '1M');

        $months = match ($periode) {
            '3M'    => 3,
            '6M'    => 6,
            default => 1,
        };

        $start = (new \DateTimeImmutable())->modify("-{$months} month")->format('Y-m-d');

        $rows = $em->getConnection()->executeQuery(
            "SELECT DATE_FORMAT(date_reception, '%Y-%m-%d') AS date, COUNT(*) AS total
             FROM histo_gf_deposee
             WHERE date_reception >= :start
             GROUP BY date
             ORDER BY date ASC",
            ['start' => $start]
        )->fetchAllAssociative();

        return $this->json(
            array_map(fn($r) => ['date' => $r['date'], 'total' => (int) $r['total']], $rows)
        );
    }
}
