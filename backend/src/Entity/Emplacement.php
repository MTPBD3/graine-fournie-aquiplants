<?php

namespace App\Entity;

use App\Repository\EmplacementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmplacementRepository::class)]
#[ORM\Table(name: 'emplacement')]
class Emplacement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_emplacement', type: 'integer')]
    private ?int $idEmplacement = null;

    #[ORM\Column(name: 'lettre_etagere', type: 'string', columnDefinition: "ENUM('A','B','C','D') NOT NULL")]
    private string $lettreEtagere;

    #[ORM\Column(name: 'numero_etage', type: 'integer')]
    private int $numeroEtage;

    #[ORM\OneToMany(mappedBy: 'emplacement', targetEntity: GfClient::class)]
    private Collection $sachets;

    public function __construct()
    {
        $this->sachets = new ArrayCollection();
    }

    public function getIdEmplacement(): ?int { return $this->idEmplacement; }

    public function getLettreEtagere(): string { return $this->lettreEtagere; }
    public function setLettreEtagere(string $lettreEtagere): static { $this->lettreEtagere = $lettreEtagere; return $this; }

    public function getNumeroEtage(): int { return $this->numeroEtage; }
    public function setNumeroEtage(int $numeroEtage): static { $this->numeroEtage = $numeroEtage; return $this; }

    public function getSachets(): Collection { return $this->sachets; }
}
