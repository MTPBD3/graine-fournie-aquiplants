<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\GfClient;
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
        if (empty($depots)) return null;
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
            'statut'             => $latest ? $latest->getStatut() : 'en_attente',
            'histoDepotId'       => $latest ? $latest->getIdHistoDepot() : null,
            'client' => [
                'id'     => $g->getClient()->getIdClient(),
                'nom'    => $g->getClient()->getNomClient(),
                'prenom' => $g->getClient()->getPrenomClient(),
            ],
            'plant' => [
                'id'        => $g->getPlant()->getIdPlant(),
                'nomPlant'  => $g->getPlant()->getNomPlant(),
                'nomEspece' => $g->getPlant()->getNomEspece(),
            ],
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(GfClientRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/alertes', methods: ['GET'])]
    public function alertes(GfClientRepository $repo): JsonResponse
    {
        $data = array_filter($repo->findAll(), fn(GfClient $g) =>
            $g->getQuantiteDisponible() <= $g->getSeuilAlerte()
        );
        return $this->json(array_values(array_map(fn(GfClient $g) => [
            'id'        => $g->getIdGfClient(),
            'plante'    => $g->getPlant()->getNomPlant(),
            'numeroLot' => $g->getNumeroLot(),
            'client'    => ['nom' => $g->getClient()->getNomClient()],
            'quantite'  => $g->getQuantiteDisponible(),
            'seuil'     => $g->getSeuilAlerte(),
            'statut'    => ($this->getLatestHistoDepot($g) ? $this->getLatestHistoDepot($g)->getStatut() : 'en_attente'),
        ], $data)));
    }

    #[Route('/{id}/sachets-compatibles', methods: ['GET'])]
    public function sachetsCompatibles(int $id, GfClientRepository $repo): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) return $this->json(['message' => 'Sachet introuvable'], 404);

        $compatibles = array_filter($repo->findAll(), function (GfClient $c) use ($g) {
            if ($c->getIdGfClient() === $g->getIdGfClient()) return false;
            if ($c->getClient()->getIdClient() !== $g->getClient()->getIdClient()) return false;
            if ($c->getPlant()->getIdPlant() !== $g->getPlant()->getIdPlant()) return false;
            $latest = $this->getLatestHistoDepot($c);
            if ($latest && $latest->getStatut() === 'epuise') return false;
            if ($c->getQuantiteDisponible() <= 0) return false;
            return true;
        });

        usort($compatibles, fn($a, $b) => $a->getQuantiteDisponible() - $b->getQuantiteDisponible());

        return $this->json(array_values(array_map(function (GfClient $c) {
            $empl = $c->getEmplacement();
            return [
                'id'                 => $c->getIdGfClient(),
                'numeroLot'          => $c->getNumeroLot(),
                'quantiteDisponible' => $c->getQuantiteDisponible(),
                'emplacement'        => $empl ? ['id' => $empl->getIdEmplacement(), 'code' => $empl->getLettreEtagere() . '-' . $empl->getNumeroEtage()] : null,
            ];
        }, $compatibles)));
    }

    #[Route('/{id}/utiliser', methods: ['POST'])]
    public function utiliser(int $id, Request $request, GfClientRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) return $this->json(['message' => 'Sachet introuvable'], 404);

        $data  = json_decode($request->getContent(), true);
        $idUv  = (int) ($data['idUv'] ?? 0);
        $nbGpm = (int) ($data['nbGraineParMotte'] ?? 0);
        $uv    = $em->getRepository(Uv::class)->find($idUv);
        if (!$uv) return $this->json(['message' => 'UV introuvable'], 400);

        $nb = $nbGpm ?: $uv->getNbGraineParMotte();

        $utilisations = isset($data['utilisations']) && is_array($data['utilisations'])
            ? $data['utilisations']
            : [['idGfClient' => $id, 'quantite' => (int) ($data['quantiteUtilisee'] ?? 0)]];

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        foreach ($utilisations as $u) {
            $qte    = (int) ($u['quantite'] ?? 0);
            if ($qte <= 0) continue;
            $sachet = $repo->find((int) ($u['idGfClient'] ?? 0));
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
                $latest = $this->getLatestHistoDepot($sachet);
                if ($latest) $latest->setStatut('epuise');
                $emplacement = $sachet->getEmplacement();
                if ($emplacement) {
                    $sachet->setEmplacement(null);
                    $em->flush();
                    $em->refresh($emplacement);
                    if ($emplacement->getSachets()->isEmpty()) $em->remove($emplacement);
                }
            }
        }

        $em->flush();

        foreach ($utilisations as $u) {
            $qte    = (int) ($u['quantite'] ?? 0);
            $sachet = $repo->find((int) ($u['idGfClient'] ?? 0));
            if ($qte > 0 && $sachet) {
                $logService->log($em, $user, 'utilisation_sachet',
                    $qte . ' graines utilisées sur sachet lot ' . $sachet->getNumeroLot()
                );
            }
        }

        return $this->json($this->serialize($g));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, GfClientRepository $repo): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) return $this->json(['message' => 'Sachet introuvable'], 404);
        return $this->json($this->serialize($g));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $data        = json_decode($request->getContent(), true);
        $referenceGf = trim($data['referenceGf'] ?? '');
        $numeroLot   = trim($data['numeroLot'] ?? '');
        $quantiteRaw = $data['quantiteDisponible'] ?? null;

        if ($referenceGf === '') return $this->json(['message' => 'La référence GF est obligatoire'], 400);
        if (strlen($referenceGf) > 50) return $this->json(['message' => 'La référence GF ne peut pas dépasser 50 caractères'], 400);
        if ($numeroLot === '') return $this->json(['message' => 'Le numéro de lot est obligatoire'], 400);
        if (strlen($numeroLot) > 50) return $this->json(['message' => 'Le numéro de lot ne peut pas dépasser 50 caractères'], 400);
        if ($quantiteRaw === null || !ctype_digit((string) $quantiteRaw) || (int) $quantiteRaw < 0) {
            return $this->json(['message' => 'La quantité disponible doit être un entier positif ou nul'], 400);
        }

        $client = $em->getRepository(Client::class)->find((int) ($data['idClient'] ?? 0));
        $plant  = $em->getRepository(Plant::class)->find((int) ($data['idPlant'] ?? 0));
        if (!$client) return $this->json(['message' => 'Client introuvable'], 400);
        if (!$plant)  return $this->json(['message' => 'Plant introuvable'], 400);

        try {
            $g = new GfClient();
            $g->setReferenceGf($referenceGf);
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
        if (!$g) return $this->json(['message' => 'Sachet introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['referenceGf']))        $g->setReferenceGf($data['referenceGf']);
        if (isset($data['numeroLot']))          $g->setNumeroLot($data['numeroLot']);
        if (isset($data['quantiteDisponible'])) $g->setQuantiteDisponible($data['quantiteDisponible']);
        if (isset($data['seuilAlerte']))        $g->setSeuilAlerte($data['seuilAlerte']);
        if (isset($data['nomClient']))          $g->setNomClient($data['nomClient']);
        if (isset($data['idClient'])) {
            $c = $em->getRepository(Client::class)->find($data['idClient']);
            if ($c) $g->setClient($c);
        }
        if (isset($data['idPlant'])) {
            $p = $em->getRepository(Plant::class)->find($data['idPlant']);
            if ($p) $g->setPlant($p);
        }
        $em->flush();

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $logService->log($em, $user, 'modification_sachet', 'Sachet lot ' . $g->getNumeroLot() . ' modifié');
        return $this->json(['message' => 'Sachet mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, GfClientRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $g = $repo->find($id);
        if (!$g) return $this->json(['message' => 'Sachet introuvable'], 404);

        $numeroLot = $g->getNumeroLot();
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $em->remove($g);
        $em->flush();
        $logService->log($em, $user, 'suppression_sachet', 'Sachet lot ' . $numeroLot . ' supprimé');
        return $this->json(['message' => 'Sachet supprimé']);
    }
}
