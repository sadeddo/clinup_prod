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
}
