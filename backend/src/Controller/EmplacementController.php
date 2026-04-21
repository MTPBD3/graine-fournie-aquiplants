<?php

namespace App\Controller;

use App\Entity\Emplacement;
use App\Entity\GfClient;
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
    private function serializeGfClient(GfClient $gf): array
    {
        return [
            'id'                 => $gf->getIdGfClient(),
            'numeroLot'          => $gf->getNumeroLot(),
            'nomClient'          => $gf->getNomClient(),
            'quantiteDisponible' => $gf->getQuantiteDisponible(),
            'statut'             => (function () use ($gf) {
                $depots = $gf->getHistoDepots()->toArray();
                if (empty($depots)) return 'en_attente';
                usort($depots, fn($a, $b) => $b->getIdHistoDepot() - $a->getIdHistoDepot());
                return $depots[0]->getStatut();
            })(),
            'plant'  => ['id' => $gf->getPlant()->getIdPlant(), 'nomPlant' => $gf->getPlant()->getNomPlant()],
            'client' => ['id' => $gf->getClient()->getIdClient(), 'nom' => $gf->getClient()->getNomClient(), 'prenom' => $gf->getClient()->getPrenomClient()],
        ];
    }

    private function serialize(Emplacement $e): array
    {
        return [
            'id'      => $e->getIdEmplacement(),
            'code'    => $e->getLettreEtagere() . '-' . $e->getNumeroEtage(),
            'sachets' => array_map([$this, 'serializeGfClient'], $e->getSachets()->toArray()),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(EmplacementRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/libres', methods: ['GET'])]
    public function libres(EmplacementRepository $repo): JsonResponse
    {
        $all = $repo->findAll();
        $libres = array_filter($all, fn($e) => $e->getSachets()->isEmpty());
        return $this->json(array_map([$this, 'serialize'], array_values($libres)));
    }

    #[Route('/assigner', methods: ['POST'])]
    public function assigner(Request $request, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $lettre   = strtoupper($data['lettreEtagere'] ?? '');
        $etage    = (int) ($data['numeroEtage'] ?? 0);
        $idSachet = (int) ($data['idGfClient'] ?? 0);

        $sachet = $em->getRepository(GfClient::class)->find($idSachet);
        if (!$sachet) return $this->json(['message' => 'Sachet introuvable'], 404);

        $emp = $em->getRepository(Emplacement::class)->findOneBy([
            'lettreEtagere' => $lettre,
            'numeroEtage'   => $etage,
        ]);
        if (!$emp) {
            $emp = new Emplacement();
            $emp->setLettreEtagere($lettre);
            $emp->setNumeroEtage($etage);
            $em->persist($emp);
        }

        $sachet->setEmplacement($emp);
        $em->flush();

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $logService->log($em, $user, 'rangement_sachet',
            'Sachet lot ' . $sachet->getNumeroLot() . ' rangé en ' . $lettre . '-' . $etage
        );

        return $this->json($this->serialize($emp), 201);
    }

    #[Route('/{id}/assigner', methods: ['POST'])]
    public function assignerExistant(int $id, Request $request, EmplacementRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $emp = $repo->find($id);
        if (!$emp) return $this->json(['message' => 'Emplacement introuvable'], 404);

        $data     = json_decode($request->getContent(), true);
        $idSachet = (int) ($data['idGfClient'] ?? 0);

        $sachet = $em->getRepository(GfClient::class)->find($idSachet);
        if (!$sachet) return $this->json(['message' => 'Sachet introuvable'], 404);

        $sachet->setEmplacement($emp);
        $em->flush();

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $logService->log($em, $user, 'rangement_sachet',
            'Sachet lot ' . $sachet->getNumeroLot() . ' ajouté en ' . $emp->getLettreEtagere() . '-' . $emp->getNumeroEtage()
        );

        return $this->json($this->serialize($emp));
    }

    #[Route('/{id}/liberer/{idGfClient}', methods: ['DELETE'])]
    public function liberer(int $id, int $idGfClient, EmplacementRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $emp    = $repo->find($id);
        $sachet = $em->getRepository(GfClient::class)->find($idGfClient);

        if (!$emp || !$sachet) return $this->json(['message' => 'Introuvable'], 404);

        $sachet->setEmplacement(null);
        $em->flush();
        $em->refresh($emp);

        if ($emp->getSachets()->isEmpty()) {
            $em->remove($emp);
            $em->flush();
        }

        return $this->json(null, 204);
    }
}
