<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: 'log')]
class Log
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_log', type: 'integer')]
    private ?int $idLog = null;

    #[ORM\Column(name: 'action', type: 'string', length: 100)]
    private string $action;

    #[ORM\Column(name: 'date_action', type: 'datetime')]
    private \DateTimeInterface $dateAction;

    #[ORM\Column(name: 'detail', type: 'string', length: 255, nullable: true)]
    private ?string $detail = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false)]
    private Utilisateur $utilisateur;

    public function getIdLog(): ?int { return $this->idLog; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }

    public function getDateAction(): \DateTimeInterface { return $this->dateAction; }
    public function setDateAction(\DateTimeInterface $dateAction): static { $this->dateAction = $dateAction; return $this; }

    public function getDetail(): ?string { return $this->detail; }
    public function setDetail(?string $detail): static { $this->detail = $detail; return $this; }

    public function getUtilisateur(): Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
}
