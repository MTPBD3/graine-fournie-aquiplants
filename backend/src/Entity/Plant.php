<?php

namespace App\Entity;

use App\Repository\PlantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlantRepository::class)]
#[ORM\Table(name: 'plant')]
class Plant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_plant', type: 'integer')]
    private ?int $idPlant = null;

    #[ORM\Column(name: 'nom_plant', type: 'string', length: 150)]
    private string $nomPlant;

    #[ORM\Column(name: 'nom_espece', type: 'string', length: 150)]
    private string $nomEspece;

    #[ORM\OneToMany(mappedBy: 'plant', targetEntity: GfClient::class)]
    private Collection $gfClients;

    public function __construct()
    {
        $this->gfClients = new ArrayCollection();
    }

    public function getIdPlant(): ?int { return $this->idPlant; }

    public function getNomPlant(): string { return $this->nomPlant; }
    public function setNomPlant(string $nomPlant): static { $this->nomPlant = $nomPlant; return $this; }

    public function getNomEspece(): string { return $this->nomEspece; }
    public function setNomEspece(string $nomEspece): static { $this->nomEspece = $nomEspece; return $this; }

    public function getGfClients(): Collection { return $this->gfClients; }
}
