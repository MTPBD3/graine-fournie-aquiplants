<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\LogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(UtilisateurRepository $repo): JsonResponse
    {
        $items = $repo->findAll();

        $data = array_map(fn(Utilisateur $u) => [
            'id'     => $u->getId(),
            'nom'    => $u->getNom(),
            'prenom' => $u->getPrenom(),
            'email'  => $u->getEmail(),
            'role'   => $u->getRole(),
            'roles'  => $u->getRoles(),
        ], $items);

        return $this->json($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, UtilisateurRepository $repo): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        return $this->json([
            'id'     => $u->getId(),
            'nom'    => $u->getNom(),
            'prenom' => $u->getPrenom(),
            'email'  => $u->getEmail(),
            'role'   => $u->getRole(),
            'roles'  => $u->getRoles(),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        LogService $logService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['motdepasse'])) {
            return $this->json(['message' => 'Email et mot de passe requis'], 400);
        }

        $u = new Utilisateur();
        $u->setNom($data['nom'] ?? '');
        $u->setPrenom($data['prenom'] ?? '');
        $u->setEmail($data['email']);
        $u->setRole($data['role'] ?? 'ROLE_EMPLOYE');
        $u->setMdpCrypte($hasher->hashPassword($u, $data['motdepasse']));

        $em->persist($u);
        $em->flush();

        /** @var \App\Entity\Utilisateur $actor */
        $actor = $this->getUser();
        $logService->log($em, $actor, 'creation_utilisateur',
            'Utilisateur ' . $u->getEmail() . ' créé'
        );

        return $this->json(['id' => $u->getId(), 'message' => 'Utilisateur créé'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        UtilisateurRepository $repo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $u = $repo->find($id);
        if (!$u) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom']))    $u->setNom($data['nom']);
        if (isset($data['prenom'])) $u->setPrenom($data['prenom']);
        if (isset($data['email']))  $u->setEmail($data['email']);
        if (isset($data['role']))   $u->setRole($data['role']);

        // Mise à jour mot de passe si fourni (via motdepasse ou mdpCrypte en clair)
        $newPassword = $data['motdepasse'] ?? $data['mdpCrypte'] ?? null;
        if (!empty($newPassword)) {
            $u->setMdpCrypte($hasher->hashPassword($u, $newPassword));
        }

        $em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, UtilisateurRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $email = $u->getEmail();

        /** @var \App\Entity\Utilisateur $actor */
        $actor = $this->getUser();

        $em->remove($u);
        $em->flush();

        $logService->log($em, $actor, 'suppression_utilisateur',
            'Utilisateur ' . $email . ' supprimé'
        );

        return $this->json(null, 204);
    }
}
