<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'idConversation', targetEntity: Messages::class)]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'conversations1')]
    private ?User $participant1 = null;

    #[ORM\ManyToOne(inversedBy: 'conversations2')]
    private ?User $participant2 = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Messages>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Messages $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setIdConversation($this);
        }

        return $this;
    }

    public function removeMessage(Messages $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getIdConversation() === $this) {
                $message->setIdConversation(null);
            }
        }

        return $this;
    }

    public function getParticipant1(): ?User
    {
        return $this->participant1;
    }

    public function setParticipant1(?User $participant1): static
    {
        $this->participant1 = $participant1;

        return $this;
    }

    public function getParticipant2(): ?User
    {
        return $this->participant2;
    }

    public function setParticipant2(?User $participant2): static
    {
        $this->participant2 = $participant2;

        return $this;
    }
}
