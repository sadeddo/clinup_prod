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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dtStart = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dtEnd = null;

    #[ORM\ManyToOne(inversedBy: 'icalres')]
    private ?Logement $logement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nbrHeure = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(length: 255, unique: true)]
private ?string $uid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $heure = null;

public function getUid(): ?string
{
    return $this->uid;
}

public function setUid(?string $uid): static
{
    $this->uid = $uid;
    return $this;
}


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDtStart(): ?\DateTime
    {
        return $this->dtStart;
    }

    public function setDtStart(?\DateTime $dtStart): static
    {
        $this->dtStart = $dtStart;

        return $this;
    }

    public function getDtEnd(): ?\DateTime
    {
        return $this->dtEnd;
    }

    public function setDtEnd(?\DateTime $dtEnd): static
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

    public function getHeure(): ?string
    {
        return $this->heure;
    }

    public function setHeure(?string $heure): static
    {
        $this->heure = $heure;

        return $this;
    }
}
