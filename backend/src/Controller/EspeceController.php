<?php

namespace App\Controller;

use App\Entity\Espece;
use App\Entity\Uv;
use App\Repository\EspeceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/especes')]
class EspeceController extends AbstractController
{
    private function serialize(Espece $e): array
    {
        return [
            'id'        => $e->getIdEspece(),
            'nomEspece' => $e->getNomEspece(),
        ];
    }

    private function serializeUv(Uv $u): array
    {
        return [
            'id'                     => $u->getIdUv(),
            'nomUv'                  => $u->getNomUv(),
            'nombreGraineParMotte'   => $u->getNombreGraineParMotte(),
            'nombrePlantParPlateaux' => $u->getNombrePlantParPlateaux(),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(EspeceRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}/uvs', methods: ['GET'])]
    public function uvs(int $id, EspeceRepository $repo): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Espèce introuvable'], 404);
        }

        // Plusieurs espèces partagent le même nom (une par plant dans le CSV).
        // On regroupe les UV de toutes les espèces homonymes pour que le
        // dropdown "Utiliser" ne soit jamais vide à cause d'un id_espece différent.
        $homonymes = $repo->findBy(['nomEspece' => $e->getNomEspece()]);

        $uvs  = [];
        $seen = [];
        foreach ($homonymes as $homonyme) {
            foreach ($homonyme->getUvs() as $uv) {
                if (!isset($seen[$uv->getIdUv()])) {
                    $uvs[]                  = $uv;
                    $seen[$uv->getIdUv()]   = true;
                }
            }
        }

        return $this->json(array_map([$this, 'serializeUv'], $uvs));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $nomEspece = trim($data['nomEspece'] ?? '');
        if ($nomEspece === '') {
            return $this->json(['message' => 'Le nom de l\'espèce est obligatoire'], 400);
        }

        $e = new Espece();
        $e->setNomEspece($nomEspece);

        $em->persist($e);
        $em->flush();

        return $this->json($this->serialize($e), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EspeceRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Espèce introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['nomEspece'])) $e->setNomEspece($data['nomEspece']);

        $em->flush();

        return $this->json($this->serialize($e));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EspeceRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $e = $repo->find($id);
        if (!$e) {
            return $this->json(['message' => 'Espèce introuvable'], 404);
        }

        $em->remove($e);
        $em->flush();

        return $this->json(null, 204);
    }
}
