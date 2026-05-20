<?php

namespace App\Controller;

use App\Entity\Espece;
use App\Entity\Plant;
use App\Repository\PlantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/plants')]
class PlantController extends AbstractController
{
    private function serialize(Plant $p): array
    {
        return [
            'id'        => $p->getIdPlant(),
            'nomPlant'  => $p->getNomPlant(),
            'nomEspece' => $p->getEspece()?->getNomEspece() ?? '',
            'idEspece'  => $p->getEspece()?->getIdEspece(),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(PlantRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, PlantRepository $repo): JsonResponse
    {
        $p = $repo->find($id);
        if (!$p) {
            return $this->json(['message' => 'Plante introuvable'], 404);
        }
        return $this->json($this->serialize($p));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $nomPlant = trim($data['nomPlant'] ?? '');
        if ($nomPlant === '') {
            return $this->json(['message' => 'Le nom de la plante est obligatoire'], 400);
        }

        $p = new Plant();
        $p->setNomPlant($nomPlant);

        if (!empty($data['idEspece'])) {
            $espece = $em->getRepository(Espece::class)->find((int) $data['idEspece']);
            if ($espece) $p->setEspece($espece);
        }

        $em->persist($p);
        $em->flush();

        return $this->json(['id' => $p->getIdPlant(), 'message' => 'Plante créée'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, PlantRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $p = $repo->find($id);
        if (!$p) {
            return $this->json(['message' => 'Plante introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nomPlant'])) $p->setNomPlant($data['nomPlant']);

        if (isset($data['idEspece'])) {
            $espece = $em->getRepository(Espece::class)->find((int) $data['idEspece']);
            if ($espece) $p->setEspece($espece);
        }

        $em->flush();

        return $this->json(['message' => 'Plante mise à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, PlantRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $p = $repo->find($id);
        if (!$p) {
            return $this->json(['message' => 'Plante introuvable'], 404);
        }

        $em->remove($p);
        $em->flush();

        return $this->json(null, 204);
    }
}
