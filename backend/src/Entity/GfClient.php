<?php

namespace App\Entity;

use App\Repository\GfClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GfClientRepository::class)]
#[ORM\Table(name: 'gf_client')]
#[ORM\UniqueConstraint(name: 'uq_numero_lot', columns: ['numero_lot'])]
#[UniqueEntity(fields: ['numeroLot'], message: 'Ce numéro de lot existe déjà.')]
class GfClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_gf_client', type: 'integer')]
    private ?int $idGfClient = null;

    #[ORM\Column(name: 'numero_lot', type: 'string', length: 50)]
    private string $numeroLot;

    #[ORM\Column(name: 'quantite_disponible', type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $quantiteDisponible;

    #[ORM\Column(name: 'seuil_alerte', type: 'integer')]
    #[Assert\PositiveOrZero]
    private int $seuilAlerte;

    #[ORM\Column(name: 'nom_client', type: 'string', length: 150)]
    private string $nomClient;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'gfClients')]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', nullable: false)]
    private Client $client;

    #[ORM\ManyToOne(targetEntity: Plant::class, inversedBy: 'gfClients')]
    #[ORM\JoinColumn(name: 'id_plant', referencedColumnName: 'id_plant', nullable: false)]
    private Plant $plant;

    #[ORM\ManyToOne(targetEntity: Emplacement::class, inversedBy: 'sachets')]
    #[ORM\JoinColumn(name: 'id_emplacement', referencedColumnName: 'id_emplacement', nullable: true)]
    private ?Emplacement $emplacement = null;

    #[ORM\OneToMany(mappedBy: 'gfClient', targetEntity: HistoGfDeposee::class)]
    private Collection $histoDepots;

    #[ORM\OneToMany(mappedBy: 'gfClient', targetEntity: GfHistoClient::class)]
    private Collection $gfHistoClients;

    public function __construct()
    {
        $this->histoDepots = new ArrayCollection();
        $this->gfHistoClients = new ArrayCollection();
    }

    public function getIdGfClient(): ?int { return $this->idGfClient; }

    public function getNumeroLot(): string { return $this->numeroLot; }
    public function setNumeroLot(string $numeroLot): static { $this->numeroLot = $numeroLot; return $this; }

    public function getQuantiteDisponible(): int { return $this->quantiteDisponible; }
    public function setQuantiteDisponible(int $quantiteDisponible): static { $this->quantiteDisponible = $quantiteDisponible; return $this; }

    public function getSeuilAlerte(): int { return $this->seuilAlerte; }
    public function setSeuilAlerte(int $seuilAlerte): static { $this->seuilAlerte = $seuilAlerte; return $this; }

    public function getNomClient(): string { return $this->nomClient; }
    public function setNomClient(string $nomClient): static { $this->nomClient = $nomClient; return $this; }

    public function getClient(): Client { return $this->client; }
    public function setClient(Client $client): static { $this->client = $client; return $this; }

    public function getPlant(): Plant { return $this->plant; }
    public function setPlant(Plant $plant): static { $this->plant = $plant; return $this; }

    public function getEmplacement(): ?Emplacement { return $this->emplacement; }
    public function setEmplacement(?Emplacement $emplacement): static { $this->emplacement = $emplacement; return $this; }

    public function getHistoDepots(): Collection { return $this->histoDepots; }
    public function getGfHistoClients(): Collection { return $this->gfHistoClients; }
}
