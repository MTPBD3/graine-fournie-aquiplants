<?php

namespace App\Controller;

use App\Entity\Espece;
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
        return [
            'id'                     => $u->getIdUv(),
            'nomUv'                  => $u->getNomUv(),
            'nombreGraineParMotte'   => $u->getNombreGraineParMotte(),
            'nombrePlantParPlateaux' => $u->getNombrePlantParPlateaux(),
            'espece' => [
                'id'        => $u->getEspece()->getIdEspece(),
                'nomEspece' => $u->getEspece()->getNomEspece(),
            ],
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(UvRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $nomUv                  = trim($data['nomUv'] ?? '');
        $nombreGraineParMotte   = (int) ($data['nombreGraineParMotte'] ?? 0);
        $nombrePlantParPlateaux = (int) ($data['nombrePlantParPlateaux'] ?? 0);
        $idEspece               = (int) ($data['idEspece'] ?? 0);

        if ($nomUv === '' || $nombreGraineParMotte <= 0 || $nombrePlantParPlateaux <= 0) {
            return $this->json(['message' => 'Nom UV, nombre de graines par motte et nombre de plants par plateaux requis'], 400);
        }

        $espece = $em->getRepository(Espece::class)->find($idEspece);
        if (!$espece) {
            return $this->json(['message' => "Espèce introuvable (idEspece=$idEspece)"], 400);
        }

        $u = new Uv();
        $u->setNomUv($nomUv);
        $u->setNombreGraineParMotte($nombreGraineParMotte);
        $u->setNombrePlantParPlateaux($nombrePlantParPlateaux);
        $u->setEspece($espece);

        $em->persist($u);
        $em->flush();

        return $this->json($this->serialize($u), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, UvRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) {
            return $this->json(['message' => 'UV introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nomUv']))                  $u->setNomUv($data['nomUv']);
        if (isset($data['nombreGraineParMotte']))    $u->setNombreGraineParMotte((int) $data['nombreGraineParMotte']);
        if (isset($data['nombrePlantParPlateaux']))  $u->setNombrePlantParPlateaux((int) $data['nombrePlantParPlateaux']);

        if (isset($data['idEspece'])) {
            $espece = $em->getRepository(Espece::class)->find((int) $data['idEspece']);
            if ($espece) $u->setEspece($espece);
        }

        $em->flush();

        return $this->json($this->serialize($u));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, UvRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) {
            return $this->json(['message' => 'UV introuvable'], 404);
        }

        $em->remove($u);
        $em->flush();

        return $this->json(null, 204);
    }
}
