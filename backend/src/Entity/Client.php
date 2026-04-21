<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_client', type: 'integer')]
    private ?int $idClient = null;

    #[ORM\Column(name: 'nom_client', type: 'string', length: 150)]
    private string $nomClient;

    #[ORM\Column(name: 'prenom_client', type: 'string', length: 150)]
    private string $prenomClient;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: GfClient::class)]
    private Collection $gfClients;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: CommandeASemer::class)]
    private Collection $commandes;

    public function __construct()
    {
        $this->gfClients = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function getIdClient(): ?int { return $this->idClient; }

    public function getNomClient(): string { return $this->nomClient; }
    public function setNomClient(string $nomClient): static { $this->nomClient = $nomClient; return $this; }

    public function getPrenomClient(): string { return $this->prenomClient; }
    public function setPrenomClient(string $prenomClient): static { $this->prenomClient = $prenomClient; return $this; }

    public function getGfClients(): Collection { return $this->gfClients; }
    public function getCommandes(): Collection { return $this->commandes; }
}
