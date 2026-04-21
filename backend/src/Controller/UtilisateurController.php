<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    private function serialize(Utilisateur $u): array
    {
        return [
            'id'     => $u->getId(),
            'nom'    => $u->getNom(),
            'prenom' => $u->getPrenom(),
            'email'  => $u->getEmail(),
            'role'   => $u->getRole(),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function index(UtilisateurRepository $repo): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $repo->findAll()));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, UtilisateurRepository $repo): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) return $this->json(['message' => 'Utilisateur introuvable'], 404);
        return $this->json($this->serialize($u));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $u = new Utilisateur();
        $u->setNom(trim($data['nom'] ?? ''));
        $u->setPrenom(trim($data['prenom'] ?? ''));
        $u->setEmail(trim($data['email'] ?? ''));
        $u->setRole($data['role'] ?? 'employe');
        $u->setMdpCrypte($hasher->hashPassword($u, $data['password'] ?? 'password'));

        $em->persist($u);
        $em->flush();

        return $this->json(['id' => $u->getId(), 'message' => 'Utilisateur créé'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, UtilisateurRepository $repo, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) return $this->json(['message' => 'Utilisateur introuvable'], 404);

        $data = json_decode($request->getContent(), true);
        if (isset($data['nom']))    $u->setNom($data['nom']);
        if (isset($data['prenom'])) $u->setPrenom($data['prenom']);
        if (isset($data['email']))  $u->setEmail($data['email']);
        if (isset($data['role']))   $u->setRole($data['role']);
        if (!empty($data['password'])) {
            $u->setMdpCrypte($hasher->hashPassword($u, $data['password']));
        }

        $em->flush();
        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, UtilisateurRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) return $this->json(['message' => 'Utilisateur introuvable'], 404);
        $em->remove($u);
        $em->flush();
        return $this->json(null, 204);
    }
}
