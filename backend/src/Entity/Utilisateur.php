<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'mdp_crypte', length: 255)]
    private ?string $mdpCrypte = null;

    #[ORM\Column(length: 20)]
    private ?string $role = 'employe';

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getMdpCrypte(): ?string { return $this->mdpCrypte; }
    public function setMdpCrypte(string $mdpCrypte): static { $this->mdpCrypte = $mdpCrypte; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    // UserInterface
    public function getRoles(): array { return [in_array($this->role, ['admin', 'ROLE_ADMIN'], true) ? 'ROLE_ADMIN' : 'ROLE_EMPLOYE']; }
    public function getPassword(): ?string { return $this->mdpCrypte; }
    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
}
