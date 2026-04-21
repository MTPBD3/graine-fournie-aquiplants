<?php

namespace App\Entity;

use App\Repository\GfHistoClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GfHistoClientRepository::class)]
#[ORM\Table(name: 'gf_histo_client')]
class GfHistoClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_histo', type: 'integer')]
    private ?int $idHisto = null;

    #[ORM\Column(name: 'quantite_semee', type: 'integer')]
    private int $quantiteSemee;

    #[ORM\Column(name: 'date_semis', type: 'date')]
    private \DateTimeInterface $dateSemis;

    #[ORM\Column(name: 'nb_graine_par_motte', type: 'integer')]
    private int $nbGraineParMotte;

    #[ORM\Column(name: 'nom_uv', type: 'string', length: 100)]
    private string $nomUv;

    #[ORM\ManyToOne(targetEntity: GfClient::class, inversedBy: 'gfHistoClients')]
    #[ORM\JoinColumn(name: 'id_gf_client', referencedColumnName: 'id_gf_client', nullable: false)]
    private GfClient $gfClient;

    #[ORM\ManyToOne(targetEntity: Uv::class, inversedBy: 'gfHistoClients')]
    #[ORM\JoinColumn(name: 'id_uv', referencedColumnName: 'id_uv', nullable: false)]
    private Uv $uv;

    public function getIdHisto(): ?int { return $this->idHisto; }

    public function getQuantiteSemee(): int { return $this->quantiteSemee; }
    public function setQuantiteSemee(int $quantiteSemee): static { $this->quantiteSemee = $quantiteSemee; return $this; }

    public function getDateSemis(): \DateTimeInterface { return $this->dateSemis; }
    public function setDateSemis(\DateTimeInterface $dateSemis): static { $this->dateSemis = $dateSemis; return $this; }

    public function getNbGraineParMotte(): int { return $this->nbGraineParMotte; }
    public function setNbGraineParMotte(int $nbGraineParMotte): static { $this->nbGraineParMotte = $nbGraineParMotte; return $this; }

    public function getNomUv(): string { return $this->nomUv; }
    public function setNomUv(string $nomUv): static { $this->nomUv = $nomUv; return $this; }

    public function getGfClient(): GfClient { return $this->gfClient; }
    public function setGfClient(GfClient $gfClient): static { $this->gfClient = $gfClient; return $this; }

    public function getUv(): Uv { return $this->uv; }
    public function setUv(Uv $uv): static { $this->uv = $uv; return $this; }
}
