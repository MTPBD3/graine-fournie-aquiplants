<?php

namespace App\Controller;

use App\Entity\HistoGfDeposee;
use App\Entity\GfClient;
use App\Repository\HistoGfDeposeeRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/histo-gf-deposees')]
class HistoGfDeposeeController extends AbstractController
{
    private function serialize(HistoGfDeposee $h): array
    {
        return [
            'id'              => $h->getIdHistoDepot(),
            'quantiteDeposee' => $h->getQuantiteDeposee(),
            'dateReception'   => $h->getDateReception()->format('Y-m-d'),
            'statut'          => $h->getStatut(),
            'note'            => $h->getNote(),
            'gfClient' => [
                'id'          => $h->getGfClient()->getIdGfClient(),
                'numeroLot'   => $h->getGfClient()->getNumeroLot(),
                'nomClient'   => $h->getGfClient()->getNomClient(),
                'plant' => [
                    'id'       => $h->getGfClient()->getPlant()->getIdPlant(),
                    'nomPlant' => $h->getGfClient()->getPlant()->getNomPlant(),
                ],
            ],
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(HistoGfDeposeeRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, HistoGfDeposeeRepository $repo): JsonResponse
    {
        $h = $repo->find($id);
        if (!$h) {
            return $this->json(['message' => 'Historique introuvable'], 404);
        }
        return $this->json($this->serialize($h));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $gfClient = $em->getRepository(GfClient::class)->find($data['idGfClient'] ?? 0);
        if (!$gfClient) {
            return $this->json(['message' => 'GfClient introuvable'], 400);
        }

        try {
            $date = new \DateTime($data['dateReception'] ?? 'now');
        } catch (\Exception $e) {
            return $this->json(['message' => 'Format de date invalide (attendu: Y-m-d)'], 400);
        }

        $h = new HistoGfDeposee();
        $h->setQuantiteDeposee($data['quantiteDeposee'] ?? 0);
        $h->setDateReception($date);
        $h->setStatut($data['statut'] ?? 'a_traiter');
        $h->setNote($data['note'] ?? null);
        $h->setGfClient($gfClient);

        $em->persist($h);
        $em->flush();

        return $this->json(['id' => $h->getIdHistoDepot(), 'message' => 'Historique créé'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, HistoGfDeposeeRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $h = $repo->find($id);
        if (!$h) {
            return $this->json(['message' => 'Historique introuvable'], 404);
        }

        $data        = json_decode($request->getContent(), true);
        $statutAvant = $h->getStatut();

        if (isset($data['statut']))          $h->setStatut($data['statut']);
        if (isset($data['quantiteDeposee'])) $h->setQuantiteDeposee($data['quantiteDeposee']);
        if (isset($data['note']))            $h->setNote($data['note']);
        if (isset($data['dateReception']))   $h->setDateReception(new \DateTime($data['dateReception']));

        $em->flush();

        if (($data['statut'] ?? null) === 'range' && $statutAvant !== 'range') {
            /** @var \App\Entity\Utilisateur $user */
            $user = $this->getUser();
            $logService->log($em, $user, 'changement_statut',
                'Sachet lot ' . $h->getGfClient()->getNumeroLot() . ' marqué comme rangé'
            );
        }

        return $this->json(['message' => 'Historique mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, HistoGfDeposeeRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $h = $repo->find($id);
        if (!$h) {
            return $this->json(['message' => 'Historique introuvable'], 404);
        }

        $em->remove($h);
        $em->flush();

        return $this->json(null, 204);
    }
}
