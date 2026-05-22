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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/utilisateurs')]
class UtilisateurController extends AbstractController
{
    private const ROLES_AUTORISES = ['ROLE_ADMIN', 'ROLE_EMPLOYE'];

    private function validatePassword(string $password): ?string
    {
        if (strlen($password) < 10) {
            return 'Le mot de passe doit contenir au moins 10 caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Le mot de passe doit contenir au moins une lettre majuscule.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Le mot de passe doit contenir au moins un chiffre.';
        }
        return null;
    }

    // Endpoint accessible à tout utilisateur authentifié : mise à jour de son propre profil
    #[Route('/mon-profil', methods: ['PATCH'])]
    public function monProfil(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $newPassword = $data['motdepasse'] ?? null;
        if (empty($newPassword)) {
            return $this->json(['message' => 'Mot de passe requis'], 400);
        }

        $error = $this->validatePassword($newPassword);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $user->setMdpCrypte($hasher->hashPassword($user, $newPassword));
        $em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour']);
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
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

        $error = $this->validatePassword($data['motdepasse']);
        if ($error !== null) {
            return $this->json(['message' => $error], 422);
        }

        $role = $data['role'] ?? 'ROLE_EMPLOYE';
        if (!in_array($role, self::ROLES_AUTORISES, true)) {
            return $this->json(['message' => 'Rôle invalide. Valeurs acceptées : ROLE_ADMIN, ROLE_EMPLOYE'], 422);
        }

        $u = new Utilisateur();
        $u->setNom($data['nom'] ?? '');
        $u->setPrenom($data['prenom'] ?? '');
        $u->setEmail($data['email']);
        $u->setRole($role);
        $u->setMdpCrypte($hasher->hashPassword($u, $data['motdepasse']));

        $em->persist($u);
        $em->flush();

        /** @var Utilisateur $actor */
        $actor = $this->getUser();
        $logService->log($em, $actor, 'creation_utilisateur',
            'Utilisateur #' . $u->getId() . ' créé'
        );

        return $this->json(['id' => $u->getId(), 'message' => 'Utilisateur créé'], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
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

        if (isset($data['role'])) {
            if (!in_array($data['role'], self::ROLES_AUTORISES, true)) {
                return $this->json(['message' => 'Rôle invalide. Valeurs acceptées : ROLE_ADMIN, ROLE_EMPLOYE'], 422);
            }
            $u->setRole($data['role']);
        }

        if (isset($data['nom']))    $u->setNom($data['nom']);
        if (isset($data['prenom'])) $u->setPrenom($data['prenom']);
        if (isset($data['email']))  $u->setEmail($data['email']);

        $newPassword = $data['motdepasse'] ?? $data['mdpCrypte'] ?? null;
        if (!empty($newPassword)) {
            $error = $this->validatePassword($newPassword);
            if ($error !== null) {
                return $this->json(['message' => $error], 422);
            }
            $u->setMdpCrypte($hasher->hashPassword($u, $newPassword));
        }

        $em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, UtilisateurRepository $repo, EntityManagerInterface $em, LogService $logService): JsonResponse
    {
        $u = $repo->find($id);
        if (!$u) {
            return $this->json(['message' => 'Utilisateur introuvable'], 404);
        }

        $userId = $u->getId();

        /** @var Utilisateur $actor */
        $actor = $this->getUser();

        $em->remove($u);
        $em->flush();

        $logService->log($em, $actor, 'suppression_utilisateur',
            'Utilisateur #' . $userId . ' supprimé'
        );

        return $this->json(null, 204);
    }
}
