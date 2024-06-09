<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subscriptionId = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dtStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dtEnd = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ammount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idProdStripe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subscriptionItemId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(?string $subscriptionId): static
    {
        $this->subscriptionId = $subscriptionId;

        return $this;
    }

    public function getDtStart(): ?\DateTimeInterface
    {
        return $this->dtStart;
    }

    public function setDtStart(?\DateTimeInterface $dtStart): static
    {
        $this->dtStart = $dtStart;

        return $this;
    }

    public function getDtEnd(): ?\DateTimeInterface
    {
        return $this->dtEnd;
    }

    public function setDtEnd(?\DateTimeInterface $dtEnd): static
    {
        $this->dtEnd = $dtEnd;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAmmount(): ?string
    {
        return $this->ammount;
    }

    public function setAmmount(?string $ammount): static
    {
        $this->ammount = $ammount;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getIdProdStripe(): ?string
    {
        return $this->idProdStripe;
    }

    public function setIdProdStripe(?string $idProdStripe): static
    {
        $this->idProdStripe = $idProdStripe;

        return $this;
    }

    public function getSubscriptionItemId(): ?string
    {
        return $this->subscriptionItemId;
    }

    public function setSubscriptionItemId(?string $subscriptionItemId): static
    {
        $this->subscriptionItemId = $subscriptionItemId;

        return $this;
    }
}
