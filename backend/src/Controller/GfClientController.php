<?php

namespace App\Controller;

use App\Entity\GfClient;
use App\Entity\Client;
use App\Entity\GfHistoClient;
use App\Entity\Plant;
use App\Entity\Uv;
use App\Repository\GfClientRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/gf-clients')]
class GfClientController extends AbstractController
{
    private function getLatestHistoDepot(GfClient $g): ?object
    {
        $depots = $g->getHistoDepots()->toArray();
        if (empty($depots)) {
            return null;
        }
        usort($depots, fn($a, $b) => $b->getIdHistoDepot() - $a->getIdHistoDepot());
        return $depots[0];
    }

    private function serialize(GfClient $g): array
    {
        $latest = $this->getLatestHistoDepot($g);

        return [
            'id'                 => $g->getIdGfClient(),
            'numeroLot'          => $g->getNumeroLot(),
            'quantiteDisponible' => $g->getQuantiteDisponible(),
            'seuilAlerte'        => $g->getSeuilAlerte(),
            'nomClient'          => $g->getNomClient(),
            'statut'             => $latest ? $latest->getStatut() : 'a_traiter',
            'histoDepotId'       => $latest ? $latest->getIdHistoDepot() : null,
            'client' => [
                'id'     => $g->getClient()->getIdClient(),
                'nom'    => $g->getClient()->getNomClient(),
                'prenom' => $g->getClient()->getPrenomClient(),
            ],
            'plant' => [
                'id'        => $g->getPlant()->getIdPlant(),
                'nomPlant'  => $g->getPlant()->getNomPlant(),
                'nomEspece' => $g->getPlant()->getEspece()?->getNomEspece() ?? '',
                'idEspece'  => $g->getPlant()->getEspece()?->getIdEspece(),
            ],
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(Request $request, GfClientRepository $repo): JsonResponse
    {
        $search = trim($request->query->get('search', ''));

        if ($search !== '') {
            $items = $repo->createQueryBuilder('g')
                ->where('g.numeroLot LIKE :s OR g.nomClient LIKE :s')
                ->setParameter('s', '%' . $search . '%')
                ->getQuery()
                ->getResult();
        } else {
            $items = $repo->findAll();
        }

        return $this->json(array_map([$this, 'serialize'], $items));
    }

    #[Route('/alertes', methods: ['GET'])]
    public function alertes(GfClientRepository $repo): JsonResponse
    {
        $all = $repo->findAll();

        $data = array_filter($all, fn(GfClient $g) =>
            $g->getQuantiteDisponible() <= $g->getSeuilAlerte()
        );

        $result = array_map(fn(GfClient $g) => [
            'id'         => $g->getIdGfClient(),
            'plante'     => $g->getPlant()->getNomPlant(),
            'numeroLot'  => $g->getNumeroLot(),
            'client'     => ['nom' => $g->getClient()->getNomClient()],
            'emplacement' => null, // résolu via EmplacementController si besoin
            'quantite'   => $g->getQuantiteDisponible(),
            'seuil'      => $g->getSeuilAlerte(),
            'statut'     => ($this->getLatestHistoDepot($g) ? $this->getLatestHistoDepot($g)->getStatut() : 'a_traiter'),
        ], $data);

        return $this->json(array_values($result));
    }

    #[Route('/{id}/sachets-compatibles', methods: ['GET'])]
    public function sachetsCompatibles(int $id, GfClientRepository $repo): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) {
            return $this->json(['message' => 'Sachet introuvable'], 404);
        }

        $all = $repo->findAll();
        $compatibles = array_filter($all, function (GfClient $c) use ($g) {
            if ($c->getIdGfClient() === $g->getIdGfClient()) return false;
            if ($c->getClient()->getIdClient() !== $g->getClient()->getIdClient()) return false;
            if ($c->getPlant()->getIdPlant() !== $g->getPlant()->getIdPlant()) return false;
            $latest = $this->getLatestHistoDepot($c);
            $statut = $latest ? $latest->getStatut() : 'a_traiter';
            if ($statut !== 'range') return false;
            if ($c->getQuantiteDisponible() <= 0) return false;
            return true;
        });

        $compatibles = array_values($compatibles);
        usort($compatibles, fn ($a, $b) => $a->getQuantiteDisponible() - $b->getQuantiteDisponible());

        $result = array_map(function (GfClient $c) {
            $empl = $c->getEmplacement();
            return [
                'id'                 => $c->getIdGfClient(),
                'numeroLot'          => $c->getNumeroLot(),
                'quantiteDisponible' => $c->getQuantiteDisponible(),
                'emplacement'        => $empl ? [
                    'id'   => $empl->getIdEmplacement(),
                    'code' => $empl->getLettreEtagere() . '-' . $empl->getNumeroEtage(),
                ] : null,
            ];
        }, $compatibles);

        return $this->json($result);
    }

    #[Route('/{id}/utiliser', methods: ['POST'])]
    public function utiliser(int $id, Request $request, GfClientRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) {
            return $this->json(['message' => 'Sachet introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $idUv = (int) ($data['idUv'] ?? 0);
        $nbGraineParMotteForce = (int) ($data['nbGraineParMotte'] ?? 0);

        $uv = $em->getRepository(Uv::class)->find($idUv);
        if (!$uv) {
            return $this->json(['message' => 'UV introuvable'], 400);
        }

        $nb = $nbGraineParMotteForce ?: $uv->getNombreGraineParMotte();

        // Format multi-sachets (utilisations[]) ou legacy (quantiteUtilisee)
        if (isset($data['utilisations']) && is_array($data['utilisations'])) {
            $utilisations = $data['utilisations'];
        } else {
            $quantiteUtilisee = (int) ($data['quantiteUtilisee'] ?? 0);
            if ($quantiteUtilisee <= 0) {
                return $this->json(['message' => 'Quantité utilisée invalide'], 400);
            }
            $utilisations = [['idGfClient' => $id, 'quantite' => $quantiteUtilisee]];
        }

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        foreach ($utilisations as $utilisation) {
            $sachetId = (int) ($utilisation['idGfClient'] ?? 0);
            $qte = (int) ($utilisation['quantite'] ?? 0);
            if ($qte <= 0) continue;

            $sachet = $repo->find($sachetId);
            if (!$sachet) continue;

            $nouvelleQte = max(0, $sachet->getQuantiteDisponible() - $qte);
            $sachet->setQuantiteDisponible($nouvelleQte);

            $histo = new GfHistoClient();
            $histo->setQuantiteSemee($qte);
            $histo->setDateSemis(new \DateTime('today'));
            $histo->setNbGraineParMotte($nb);
            $histo->setNomUv($uv->getNomUv());
            $histo->setGfClient($sachet);
            $histo->setUv($uv);
            $em->persist($histo);

            if ($nouvelleQte <= 0) {
                $emplacement = $sachet->getEmplacement();
                if ($emplacement !== null) {
                    $sachet->setEmplacement(null);
                    $em->flush();
                    $em->refresh($emplacement);
                    if ($emplacement->getSachets()->isEmpty()) {
                        $em->remove($emplacement);
                    }
                }
            }
        }

        $em->flush();

        foreach ($utilisations as $utilisation) {
            $qte = (int) ($utilisation['quantite'] ?? 0);
            if ($qte <= 0) continue;
            $sachet = $repo->find((int) ($utilisation['idGfClient'] ?? 0));
            if (!$sachet) continue;
            $logService->log($em, $user, 'utilisation_sachet',
                $qte . ' graines utilisées sur sachet lot ' . $sachet->getNumeroLot()
            );
        }

        return $this->json($this->serialize($g));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, GfClientRepository $repo): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) {
            return $this->json(['message' => 'Sachet introuvable'], 404);
        }
        return $this->json($this->serialize($g));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // ── Validation des entrées ────────────────────────────────────────────
        $numeroLot   = trim($data['numeroLot'] ?? '');
        $quantiteRaw = $data['quantiteDisponible'] ?? null;

        if ($numeroLot === '') {
            return $this->json(['message' => 'Le numéro de lot est obligatoire'], 400);
        }
        if (strlen($numeroLot) > 50) {
            return $this->json(['message' => 'Le numéro de lot ne peut pas dépasser 50 caractères'], 400);
        }
        if ($quantiteRaw === null || !ctype_digit((string) $quantiteRaw) || (int) $quantiteRaw < 0) {
            return $this->json(['message' => 'La quantité disponible doit être un entier positif ou nul'], 400);
        }

        $idClient = (int) ($data['idClient'] ?? 0);
        $idPlant  = (int) ($data['idPlant']  ?? 0);

        $client = $em->getRepository(Client::class)->find($idClient);
        $plant  = $em->getRepository(Plant::class)->find($idPlant);

        if (!$client) {
            return $this->json(['message' => "Client introuvable (idClient=$idClient)"], 400);
        }
        if (!$plant) {
            return $this->json(['message' => "Plant introuvable (idPlant=$idPlant)"], 400);
        }

        try {
            $g = new GfClient();
            $g->setNumeroLot($numeroLot);
            $g->setQuantiteDisponible((int) $quantiteRaw);
            $g->setSeuilAlerte((int) ($data['seuilAlerte'] ?? 0));
            $g->setNomClient(trim($data['nomClient'] ?? $client->getNomClient()));
            $g->setClient($client);
            $g->setPlant($plant);

            $em->persist($g);
            $em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $logService->log($em, $user, 'ajout_sachet',
            'Sachet lot ' . $g->getNumeroLot() . ' ajouté pour ' . $g->getNomClient()
        );

        return $this->json(['id' => $g->getIdGfClient(), 'message' => 'Sachet créé'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, GfClientRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) {
            return $this->json(['message' => 'Sachet introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['numeroLot']))          $g->setNumeroLot($data['numeroLot']);
        if (isset($data['quantiteDisponible'])) {
            $q = $data['quantiteDisponible'];
            if (!is_int($q) || $q < 0) {
                return $this->json(['message' => 'La quantité disponible doit être un entier positif ou nul'], 400);
            }
            $g->setQuantiteDisponible($q);
        }
        if (isset($data['seuilAlerte'])) {
            $s = $data['seuilAlerte'];
            if (!is_int($s) || $s < 0) {
                return $this->json(['message' => 'Le seuil d\'alerte doit être un entier positif ou nul'], 400);
            }
            $g->setSeuilAlerte($s);
        }
        if (isset($data['nomClient']))          $g->setNomClient($data['nomClient']);

        if (isset($data['idClient'])) {
            $client = $em->getRepository(Client::class)->find($data['idClient']);
            if ($client) $g->setClient($client);
        }
        if (isset($data['idPlant'])) {
            $plant = $em->getRepository(Plant::class)->find($data['idPlant']);
            if ($plant) $g->setPlant($plant);
        }

        $em->flush();

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $logService->log($em, $user, 'modification_sachet',
            'Sachet lot ' . $g->getNumeroLot() . ' modifié'
        );

        return $this->json(['message' => 'Sachet mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, GfClientRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) {
            return $this->json(['message' => 'Sachet introuvable'], 404);
        }

        $numeroLot = $g->getNumeroLot();

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        $em->remove($g);
        $em->flush();

        $logService->log($em, $user, 'suppression_sachet',
            'Sachet lot ' . $numeroLot . ' supprimé'
        );

        return $this->json(['message' => 'Sachet supprimé']);
    }
}
