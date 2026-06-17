<?php

namespace App\Controller;

use App\Entity\Emplacement;
use App\Entity\GfClient;
use App\Entity\HistoGfDeposee;
use App\Repository\EmplacementRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/emplacements')]
class EmplacementController extends AbstractController
{
    private const ETAGERES = ['A', 'B', 'C', 'D'];
    private const ETAGES   = [1, 2, 3, 4];

    private function serializeGfClient(GfClient $gf): array
    {
        $statut = null;
        $depots = $gf->getHistoDepots()->toArray();
        if (!empty($depots)) {
            usort($depots, fn($a, $b) => $b->getIdHistoDepot() - $a->getIdHistoDepot());
            $statut = $depots[0]->getStatut();
        }

        return [
            'id'                 => $gf->getIdGfClient(),
            'quantiteDisponible' => $gf->getQuantiteDisponible(),
            'nomClient'          => $gf->getNomClient(),
            'statut'             => $statut ?? 'a_traiter',
            'plant' => [
                'id'       => $gf->getPlant()->getIdPlant(),
                'nomPlant' => $gf->getPlant()->getNomPlant(),
            ],
            'client' => [
                'id'     => $gf->getClient()->getIdClient(),
                'nom'    => $gf->getClient()->getNomClient(),
                'prenom' => $gf->getClient()->getPrenomClient(),
            ],
        ];
    }

    private function serialize(Emplacement $e): array
    {
        return [
            'id'            => $e->getIdEmplacement(),
            'code'          => $e->getLettreEtagere() . '-' . $e->getNumeroEtage(),
            'lettreEtagere' => $e->getLettreEtagere(),
            'numeroEtage'   => $e->getNumeroEtage(),
            'sachets'       => array_map([$this, 'serializeGfClient'], $e->getSachets()->toArray()),
        ];
    }

    // ── Routes statiques (avant /{id}) ────────────────────────────────────────

    #[Route('/libres', methods: ['GET'])]
    public function libres(EmplacementRepository $repo): JsonResponse
    {
        $takenCodes = array_map(
            fn(Emplacement $e) => $e->getLettreEtagere() . '-' . $e->getNumeroEtage(),
            $repo->findAll()
        );

        $libres = [];
        foreach (self::ETAGERES as $lettre) {
            foreach (self::ETAGES as $etage) {
                $code = $lettre . '-' . $etage;
                if (!in_array($code, $takenCodes, true)) {
                    $libres[] = ['code' => $code, 'lettreEtagere' => $lettre, 'numeroEtage' => $etage];
                }
            }
        }

        return $this->json($libres);
    }

    #[Route('/occupes', methods: ['GET'])]
    public function occupes(EmplacementRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    // Crée ou réutilise l'emplacement pour la position, puis y ajoute le sachet
    #[Route('/assigner', methods: ['POST'])]
    public function assigner(Request $request, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $gfClient = $em->getRepository(GfClient::class)->find($data['idGfClient'] ?? 0);
        if (!$gfClient) {
            return $this->json(['message' => 'GfClient introuvable'], 400);
        }

        $lettreEtagere = $data['lettreEtagere'] ?? '';
        $numeroEtage   = (int) ($data['numeroEtage'] ?? 0);

        if (!in_array($lettreEtagere, self::ETAGERES, true) || !in_array($numeroEtage, self::ETAGES, true)) {
            return $this->json(['message' => 'Emplacement invalide. Valeurs acceptées : A-D / 1-4'], 400);
        }

        $e = $em->getRepository(Emplacement::class)->findOneBy([
            'lettreEtagere' => $lettreEtagere,
            'numeroEtage'   => $numeroEtage,
        ]);
        if (!$e) {
            $e = new Emplacement();
            $e->setLettreEtagere($lettreEtagere);
            $e->setNumeroEtage($numeroEtage);
            $em->persist($e);
        }

        $gfClient->setEmplacement($e);

        $depots = $gfClient->getHistoDepots()->toArray();
        if (!empty($depots)) {
            usort($depots, fn($a, $b) => $b->getIdHistoDepot() - $a->getIdHistoDepot());
            $depots[0]->setStatut('range');
        } else {
            $histo = new HistoGfDeposee();
            $histo->setStatut('range');
            $histo->setQuantiteDeposee($gfClient->getQuantiteDisponible());
            $histo->setDateReception(new \DateTime('today'));
            $histo->setGfClient($gfClient);
            $em->persist($histo);
        }

        $em->flush();

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $logService->log($em, $user, 'rangement_sachet',
            'Sachet lot ' . $gfClient->getNumeroLot() . ' rangé en ' . $lettreEtagere . '-' . $numeroEtage
        );

        $em->refresh($e);

        return $this->json($this->serialize($e), 201);
    }

    // ── Routes dynamiques ─────────────────────────────────────────────────────

    #[Route('/{id}/assigner', methods: ['POST'])]
    public function ajouterSachet(int $id, Request $request, EmplacementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Emplacement introuvable'], 404);
        }

        $data     = json_decode($request->getContent(), true);
        $gfClient = $em->getRepository(GfClient::class)->find($data['idGfClient'] ?? 0);
        if (!$gfClient) {
            return $this->json(['message' => 'GfClient introuvable'], 400);
        }

        $gfClient->setEmplacement($e);

        $depots = $gfClient->getHistoDepots()->toArray();
        if (!empty($depots)) {
            usort($depots, fn($a, $b) => $b->getIdHistoDepot() - $a->getIdHistoDepot());
            $depots[0]->setStatut('range');
        } else {
            $histo = new HistoGfDeposee();
            $histo->setStatut('range');
            $histo->setQuantiteDeposee($gfClient->getQuantiteDisponible());
            $histo->setDateReception(new \DateTime('today'));
            $histo->setGfClient($gfClient);
            $em->persist($histo);
        }

        $em->flush();
        $em->refresh($e);

        return $this->json($this->serialize($e));
    }

    #[Route('/{id}/liberer/{idGfClient}', methods: ['DELETE'])]
    public function libererSachet(int $id, int $idGfClient, EmplacementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Emplacement introuvable'], 404);
        }

        $gfClient = $em->getRepository(GfClient::class)->find($idGfClient);
        if (!$gfClient || $gfClient->getEmplacement()?->getIdEmplacement() !== $id) {
            return $this->json(['message' => 'Sachet non trouvé dans cet emplacement'], 404);
        }

        $gfClient->setEmplacement(null);
        $em->flush();

        $em->refresh($e);
        if ($e->getSachets()->isEmpty()) {
            $em->remove($e);
            $em->flush();
        }

        return $this->json(null, 204);
    }

    #[Route('/{id}/liberer', methods: ['DELETE'])]
    public function liberer(int $id, EmplacementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Emplacement introuvable'], 404);
        }

        foreach ($e->getSachets() as $sachet) {
            $sachet->setEmplacement(null);
        }
        $em->remove($e);
        $em->flush();

        return $this->json(null, 204);
    }

    // ── Routes CRUD de base ───────────────────────────────────────────────────

    #[Route('', methods: ['GET'])]
    public function index(EmplacementRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, EmplacementRepository $repo): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Emplacement introuvable'], 404);
        }

        return $this->json($this->serialize($e));
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EmplacementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Emplacement introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['lettreEtagere'])) $e->setLettreEtagere($data['lettreEtagere']);
        if (isset($data['numeroEtage']))   $e->setNumeroEtage($data['numeroEtage']);

        $em->flush();

        return $this->json(['message' => 'Emplacement mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EmplacementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Emplacement introuvable'], 404);
        }

        foreach ($e->getSachets() as $sachet) {
            $sachet->setEmplacement(null);
        }
        $em->remove($e);
        $em->flush();

        return $this->json(null, 204);
    }
}
