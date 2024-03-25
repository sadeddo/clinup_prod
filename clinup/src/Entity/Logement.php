<?php

namespace App\Entity;

use App\Repository\LogementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogementRepository::class)]
class Logement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 350)]
    private ?string $adresse = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $completAdresse = null;

    #[ORM\Column(length: 255)]
    private ?string $surface = null;

    #[ORM\Column]
    private ?int $nbrChambre = null;

    #[ORM\Column]
    private ?int $nbrBain = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'logements')]
    private ?User $hote = null;

    #[ORM\OneToMany(mappedBy: 'logement', targetEntity: Task::class)]
    private Collection $tasks;

    #[ORM\OneToMany(mappedBy: 'logement', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'logement', targetEntity: Probleme::class)]
    private Collection $problemes;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $booking = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $airbnb = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $img = null;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->problemes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getNom();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCompletAdresse(): ?string
    {
        return $this->completAdresse;
    }

    public function setCompletAdresse(string $completAdresse): static
    {
        $this->completAdresse = $completAdresse;

        return $this;
    }

    public function getSurface(): ?string
    {
        return $this->surface;
    }

    public function setSurface(string $surface): static
    {
        $this->surface = $surface;

        return $this;
    }

    public function getNbrChambre(): ?int
    {
        return $this->nbrChambre;
    }

    public function setNbrChambre(int $nbrChambre): static
    {
        $this->nbrChambre = $nbrChambre;

        return $this;
    }

    public function getNbrBain(): ?int
    {
        return $this->nbrBain;
    }

    public function setNbrBain(int $nbrBain): static
    {
        $this->nbrBain = $nbrBain;

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

    public function getHote(): ?User
    {
        return $this->hote;
    }

    public function setHote(?User $hote): static
    {
        $this->hote = $hote;

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setLogement($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getLogement() === $this) {
                $task->setLogement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setLogement($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getLogement() === $this) {
                $reservation->setLogement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Probleme>
     */
    public function getProblemes(): Collection
    {
        return $this->problemes;
    }

    public function addProbleme(Probleme $probleme): static
    {
        if (!$this->problemes->contains($probleme)) {
            $this->problemes->add($probleme);
            $probleme->setLogement($this);
        }

        return $this;
    }

    public function removeProbleme(Probleme $probleme): static
    {
        if ($this->problemes->removeElement($probleme)) {
            // set the owning side to null (unless already changed)
            if ($probleme->getLogement() === $this) {
                $probleme->setLogement(null);
            }
        }

        return $this;
    }

    public function getBooking(): ?string
    {
        return $this->booking;
    }

    public function setBooking(?string $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getAirbnb(): ?string
    {
        return $this->airbnb;
    }

    public function setAirbnb(?string $airbnb): static
    {
        $this->airbnb = $airbnb;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): static
    {
        $this->img = $img;

        return $this;
    }

}
