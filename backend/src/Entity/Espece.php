<?php

namespace App\Entity;

use App\Repository\EspeceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EspeceRepository::class)]
#[ORM\Table(name: 'espece')]
class Espece
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_espece', type: 'integer')]
    private ?int $idEspece = null;

    #[ORM\Column(name: 'nom_espece', type: 'string', length: 150)]
    private string $nomEspece;

    #[ORM\OneToMany(mappedBy: 'espece', targetEntity: Uv::class)]
    private Collection $uvs;

    #[ORM\OneToMany(mappedBy: 'espece', targetEntity: Plant::class)]
    private Collection $plants;

    public function __construct()
    {
        $this->uvs    = new ArrayCollection();
        $this->plants = new ArrayCollection();
    }

    public function getIdEspece(): ?int { return $this->idEspece; }

    public function getNomEspece(): string { return $this->nomEspece; }
    public function setNomEspece(string $nomEspece): static { $this->nomEspece = $nomEspece; return $this; }

    public function getUvs(): Collection { return $this->uvs; }
    public function getPlants(): Collection { return $this->plants; }
}
