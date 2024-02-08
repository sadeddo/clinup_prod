<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?bool $statut = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    private ?Logement $logement = null;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: ImgTask::class)]
    private Collection $imgTasks;

    public function __construct()
    {
        $this->imgTasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(?bool $statut): static
    {
        $this->statut = $statut;

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

    /**
     * @return Collection<int, ImgTask>
     */
    public function getImgTasks(): Collection
    {
        return $this->imgTasks;
    }

    public function addImgTask(ImgTask $imgTask): static
    {
        if (!$this->imgTasks->contains($imgTask)) {
            $this->imgTasks->add($imgTask);
            $imgTask->setTask($this);
        }

        return $this;
    }

    public function removeImgTask(ImgTask $imgTask): static
    {
        if ($this->imgTasks->removeElement($imgTask)) {
            // set the owning side to null (unless already changed)
            if ($imgTask->getTask() === $this) {
                $imgTask->setTask(null);
            }
        }

        return $this;
    }
}
