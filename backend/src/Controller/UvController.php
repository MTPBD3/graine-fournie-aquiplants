<?php

namespace App\Controller;

use App\Entity\Uv;
use App\Repository\UvRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/uvs')]
class UvController extends AbstractController
{
    private function serialize(Uv $u): array
    {
        return ['id' => $u->getIdUv(), 'nomUv' => $u->getNomUv(), 'nbGraineParMotte' => $u->getNbGraineParMotte()];
    }

    #[Route('', methods: ['GET'])]
    public function index(UvRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, UvRepository $repo): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) return $this->json(['message' => 'UV introuvable'], 404);
        return $this->json($this->serialize($u));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $u = new Uv();
        $u->setNomUv(trim($data['nomUv'] ?? ''));
        $u->setNbGraineParMotte((int) ($data['nbGraineParMotte'] ?? 1));
        $em->persist($u);
        $em->flush();
        return $this->json(['id' => $u->getIdUv(), 'message' => 'UV créée'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, UvRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) return $this->json(['message' => 'UV introuvable'], 404);
        $data = json_decode($request->getContent(), true);
        if (isset($data['nomUv']))           $u->setNomUv($data['nomUv']);
        if (isset($data['nbGraineParMotte'])) $u->setNbGraineParMotte((int) $data['nbGraineParMotte']);
        $em->flush();
        return $this->json(['message' => 'UV mise à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, UvRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) return $this->json(['message' => 'UV introuvable'], 404);
        $em->remove($u);
        $em->flush();
        return $this->json(null, 204);
    }
}
