<?php

namespace App\Entity;

use App\Repository\DispoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DispoRepository::class)]
class Dispo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $dtStart = null;

    #[ORM\Column(length: 255)]
    private ?string $dtEnd = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'dispos')]
    private ?User $prestataire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDtStart(): ?string
    {
        return $this->dtStart;
    }

    public function setDtStart(string $dtStart): static
    {
        $this->dtStart = $dtStart;

        return $this;
    }

    public function getDtEnd(): ?string
    {
        return $this->dtEnd;
    }

    public function setDtEnd(string $dtEnd): static
    {
        $this->dtEnd = $dtEnd;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getPrestataire(): ?User
    {
        return $this->prestataire;
    }

    public function setPrestataire(?User $prestataire): static
    {
        $this->prestataire = $prestataire;

        return $this;
    }
}
