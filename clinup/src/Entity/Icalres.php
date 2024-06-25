<?php

namespace App\Entity;

use App\Repository\IcalresRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IcalresRepository::class)]
class Icalres
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dtStart = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dtEnd = null;

    #[ORM\ManyToOne(inversedBy: 'icalres')]
    private ?Logement $logement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nbrHeure = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prix = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDtStart(): ?string
    {
        return $this->dtStart;
    }

    public function setDtStart(?string $dtStart): static
    {
        $this->dtStart = $dtStart;

        return $this;
    }

    public function getDtEnd(): ?string
    {
        return $this->dtEnd;
    }

    public function setDtEnd(?string $dtEnd): static
    {
        $this->dtEnd = $dtEnd;

        return $this;
    }

    public function getLogement(): ?Logement
    {
        return $this->logement;
    }

    public function setLogement(?Logement $logement): static
    {
        $this->logement = $logement;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNbrHeure(): ?string
    {
        return $this->nbrHeure;
    }

    public function setNbrHeure(?string $nbrHeure): static
    {
        $this->nbrHeure = $nbrHeure;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }
}
