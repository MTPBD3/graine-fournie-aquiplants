<?php

namespace App\Entity;

use App\Repository\CommandeASemeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeASemeRepository::class)]
#[ORM\Table(name: 'commande_a_semer')]
class CommandeASemer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commande', type: 'integer')]
    private ?int $idCommande = null;

    #[ORM\Column(name: 'quantite_a_semer', type: 'integer')]
    private int $quantiteASemer;

    #[ORM\Column(name: 'date_semis', type: 'date')]
    private \DateTimeInterface $dateSemis;

    #[ORM\Column(name: 'date_livraison', type: 'date')]
    private \DateTimeInterface $dateLivraison;

    #[ORM\ManyToOne(targetEntity: Uv::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'id_uv', referencedColumnName: 'id_uv', nullable: false)]
    private Uv $uv;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', nullable: false)]
    private Client $client;

    public function getIdCommande(): ?int { return $this->idCommande; }

    public function getQuantiteASemer(): int { return $this->quantiteASemer; }
    public function setQuantiteASemer(int $quantiteASemer): static { $this->quantiteASemer = $quantiteASemer; return $this; }

    public function getDateSemis(): \DateTimeInterface { return $this->dateSemis; }
    public function setDateSemis(\DateTimeInterface $dateSemis): static { $this->dateSemis = $dateSemis; return $this; }

    public function getDateLivraison(): \DateTimeInterface { return $this->dateLivraison; }
    public function setDateLivraison(\DateTimeInterface $dateLivraison): static { $this->dateLivraison = $dateLivraison; return $this; }

    public function getUv(): Uv { return $this->uv; }
    public function setUv(Uv $uv): static { $this->uv = $uv; return $this; }

    public function getClient(): Client { return $this->client; }
    public function setClient(Client $client): static { $this->client = $client; return $this; }
}
