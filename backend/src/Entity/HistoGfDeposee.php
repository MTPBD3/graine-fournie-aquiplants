<?php

namespace App\Entity;

use App\Repository\HistoGfDeposeeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoGfDeposeeRepository::class)]
#[ORM\Table(name: 'histo_gf_deposee')]
class HistoGfDeposee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_histo_depot', type: 'integer')]
    private ?int $idHistoDepot = null;

    #[ORM\Column(name: 'quantite_deposee', type: 'integer')]
    private int $quantiteDeposee;

    #[ORM\Column(name: 'date_reception', type: 'date')]
    private \DateTimeInterface $dateReception;

    #[ORM\Column(name: 'statut', type: 'string', columnDefinition: "ENUM('en_attente','en_stock','epuise') NOT NULL DEFAULT 'en_attente'")]
    private string $statut;

    #[ORM\Column(name: 'note', type: 'string', length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne(targetEntity: GfClient::class, inversedBy: 'histoDepots')]
    #[ORM\JoinColumn(name: 'id_gf_client', referencedColumnName: 'id_gf_client', nullable: false)]
    private GfClient $gfClient;

    public function getIdHistoDepot(): ?int { return $this->idHistoDepot; }

    public function getQuantiteDeposee(): int { return $this->quantiteDeposee; }
    public function setQuantiteDeposee(int $quantiteDeposee): static { $this->quantiteDeposee = $quantiteDeposee; return $this; }

    public function getDateReception(): \DateTimeInterface { return $this->dateReception; }
    public function setDateReception(\DateTimeInterface $dateReception): static { $this->dateReception = $dateReception; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }

    public function getGfClient(): GfClient { return $this->gfClient; }
    public function setGfClient(GfClient $gfClient): static { $this->gfClient = $gfClient; return $this; }
}
