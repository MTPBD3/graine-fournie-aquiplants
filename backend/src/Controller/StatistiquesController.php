<?php

namespace App\Controller;

use App\Repository\HistoGfDeposeeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/api/statistiques', methods: ['GET'])]
    public function index(HistoGfDeposeeRepository $repo): JsonResponse
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

        // Évolution mensuelle — groupé en PHP (compatibilité SQLite + MySQL)
        $evolByMois = [];
        foreach ($all as $h) {
            $mois = $h->getDateReception()->format('Y-m');
            $evolByMois[$mois] = ($evolByMois[$mois] ?? 0) + 1;
        }
        ksort($evolByMois);
        $evolutionMensuelle = array_values(array_map(
            fn($mois, $total) => ['mois' => $mois, 'total' => (int) $total],
            array_keys($evolByMois),
            $evolByMois
        ));

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
    public function depots(Request $request, HistoGfDeposeeRepository $repo): JsonResponse
    {
        $periode = $request->query->get('periode', '1M');

        $months = match ($periode) {
            '3M'    => 3,
            '6M'    => 6,
            default => 1,
        };

        $start = (new \DateTimeImmutable())->modify("-{$months} month");

        $byDate = [];
        foreach ($repo->findAll() as $h) {
            if ($h->getDateReception() >= $start) {
                $date = $h->getDateReception()->format('Y-m-d');
                $byDate[$date] = ($byDate[$date] ?? 0) + 1;
            }
        }
        ksort($byDate);

        return $this->json(
            array_values(array_map(
                fn($date, $total) => ['date' => $date, 'total' => (int) $total],
                array_keys($byDate),
                $byDate
            ))
        );
    }
}
