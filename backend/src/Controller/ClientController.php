<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clients')]
class ClientController extends AbstractController
{
    private function serialize(Client $c): array
    {
        return [
            'id'     => $c->getIdClient(),
            'nom'    => $c->getNomClient(),
            'prenom' => $c->getPrenomClient(),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(ClientRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, ClientRepository $repo): JsonResponse
    {
        $c = $repo->find($id);
        if (!$c) return $this->json(['message' => 'Client introuvable'], 404);
        return $this->json($this->serialize($c));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $c = new Client();
        $c->setNomClient(trim($data['nom'] ?? ''));
        $c->setPrenomClient(trim($data['prenom'] ?? ''));
        $em->persist($c);
        $em->flush();
        return $this->json(['id' => $c->getIdClient(), 'message' => 'Client créé'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, ClientRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $c = $repo->find($id);
        if (!$c) return $this->json(['message' => 'Client introuvable'], 404);
        $data = json_decode($request->getContent(), true);
        if (isset($data['nom']))    $c->setNomClient($data['nom']);
        if (isset($data['prenom'])) $c->setPrenomClient($data['prenom']);
        $em->flush();
        return $this->json(['message' => 'Client mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, ClientRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $c = $repo->find($id);
        if (!$c) return $this->json(['message' => 'Client introuvable'], 404);
        $em->remove($c);
        $em->flush();
        return $this->json(null, 204);
    }
}
