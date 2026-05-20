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

    #[ORM\Column(name: 'nombre_graine_par_motte', type: 'integer')]
    private int $nombreGraineParMotte;

    #[ORM\Column(name: 'nombre_plant_par_plateaux', type: 'integer')]
    private int $nombrePlantParPlateaux;

    #[ORM\ManyToOne(targetEntity: Espece::class, inversedBy: 'uvs')]
    #[ORM\JoinColumn(name: 'id_espece', referencedColumnName: 'id_espece', nullable: false)]
    private Espece $espece;

    #[ORM\OneToMany(mappedBy: 'uv', targetEntity: GfHistoClient::class)]
    private Collection $gfHistoClients;

    #[ORM\OneToMany(mappedBy: 'uv', targetEntity: CommandeASemer::class)]
    private Collection $commandes;

    public function __construct()
    {
        $this->gfHistoClients = new ArrayCollection();
        $this->commandes      = new ArrayCollection();
    }

    public function getIdUv(): ?int { return $this->idUv; }

    public function getNomUv(): string { return $this->nomUv; }
    public function setNomUv(string $nomUv): static { $this->nomUv = $nomUv; return $this; }

    public function getNombreGraineParMotte(): int { return $this->nombreGraineParMotte; }
    public function setNombreGraineParMotte(int $nombreGraineParMotte): static { $this->nombreGraineParMotte = $nombreGraineParMotte; return $this; }

    public function getNombrePlantParPlateaux(): int { return $this->nombrePlantParPlateaux; }
    public function setNombrePlantParPlateaux(int $nombrePlantParPlateaux): static { $this->nombrePlantParPlateaux = $nombrePlantParPlateaux; return $this; }

    public function getEspece(): Espece { return $this->espece; }
    public function setEspece(Espece $espece): static { $this->espece = $espece; return $this; }

    public function getGfHistoClients(): Collection { return $this->gfHistoClients; }
    public function getCommandes(): Collection { return $this->commandes; }
}
