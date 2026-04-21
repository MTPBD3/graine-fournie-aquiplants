<?php

namespace App\Entity;

use App\Repository\UvRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UvRepository::class)]
#[ORM\Table(name: 'uv')]
class Uv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_uv', type: 'integer')]
    private ?int $idUv = null;

    #[ORM\Column(name: 'nom_uv', type: 'string', length: 100)]
    private string $nomUv;

    #[ORM\Column(name: 'nb_graine_par_motte', type: 'integer')]
    private int $nbGraineParMotte;

    #[ORM\OneToMany(mappedBy: 'uv', targetEntity: GfHistoClient::class)]
    private Collection $gfHistoClients;

    #[ORM\OneToMany(mappedBy: 'uv', targetEntity: CommandeASemer::class)]
    private Collection $commandes;

    public function __construct()
    {
        $this->gfHistoClients = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function getIdUv(): ?int { return $this->idUv; }

    public function getNomUv(): string { return $this->nomUv; }
    public function setNomUv(string $nomUv): static { $this->nomUv = $nomUv; return $this; }

    public function getNbGraineParMotte(): int { return $this->nbGraineParMotte; }
    public function setNbGraineParMotte(int $nbGraineParMotte): static { $this->nbGraineParMotte = $nbGraineParMotte; return $this; }

    public function getGfHistoClients(): Collection { return $this->gfHistoClients; }
    public function getCommandes(): Collection { return $this->commandes; }
}
