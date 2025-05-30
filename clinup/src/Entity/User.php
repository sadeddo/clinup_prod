<?php

namespace App\Entity;

use DateTimeInterface;
use App\Entity\CommentPresta;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(
    fields: ['email'],
    message: 'Il existe déjà un compte avec cette adresse email.'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\OneToMany(mappedBy: 'hote', targetEntity: Logement::class)]
    private Collection $logements;

    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: Dispo::class)]
    private Collection $dispos;

    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: Justif::class)]
    private Collection $justifs;

    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: CommentPresta::class)]
    private Collection $commentPrestas;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 350, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: Experience::class)]
    private Collection $experiences;

    #[ORM\OneToMany(mappedBy: 'User', targetEntity: Notif::class)]
    private Collection $notifs;

    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: Postuler::class)]
    private Collection $postulers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $picture = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prix = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idStripe = null;

    #[ORM\Column(nullable: true)]
    private ?bool $statutStripe = null;

    #[ORM\OneToMany(mappedBy: 'hote', targetEntity: Invit::class)]
    private Collection $invits;

    #[ORM\Column(nullable: true)]
    private ?bool $VeryEmail = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $palier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $raison_sociale = null;

    /**
     * @var Collection<int, DeviceToken>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: DeviceToken::class)]
    private Collection $deviceTokens;

    public function __construct()
    {
        $this->logements = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->dispos = new ArrayCollection();
        $this->justifs = new ArrayCollection();
        $this->experiences = new ArrayCollection();
        $this->notifs = new ArrayCollection();
        $this->postulers = new ArrayCollection();
        $this->commentPrestas = new ArrayCollection();
        $this->invits = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->deviceTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     * 
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return Collection<int, Logement>
     */
    public function getLogements(): Collection
    {
        return $this->logements;
    }

    public function addLogement(Logement $logement): static
    {
        if (!$this->logements->contains($logement)) {
            $this->logements->add($logement);
            $logement->setHote($this);
        }

        return $this;
    }

    public function removeLogement(Logement $logement): static
    {
        if ($this->logements->removeElement($logement)) {
            // set the owning side to null (unless already changed)
            if ($logement->getHote() === $this) {
                $logement->setHote(null);
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
            $reservation->setPrestataire($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getPrestataire() === $this) {
                $reservation->setPrestataire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Dispo>
     */
    public function getDispos(): Collection
    {
        return $this->dispos;
    }

    public function addDispo(Dispo $dispo): static
    {
        if (!$this->dispos->contains($dispo)) {
            $this->dispos->add($dispo);
            $dispo->setPrestataire($this);
        }

        return $this;
    }

    public function removeDispo(Dispo $dispo): static
    {
        if ($this->dispos->removeElement($dispo)) {
            // set the owning side to null (unless already changed)
            if ($dispo->getPrestataire() === $this) {
                $dispo->setPrestataire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Justif>
     */
    public function getJustifs(): Collection
    {
        return $this->justifs;
    }

    public function addJustif(Justif $justif): static
    {
        if (!$this->justifs->contains($justif)) {
            $this->justifs->add($justif);
            $justif->setPrestataire($this);
        }

        return $this;
    }

    public function removeJustif(Justif $justif): static
    {
        if ($this->justifs->removeElement($justif)) {
            // set the owning side to null (unless already changed)
            if ($justif->getPrestataire() === $this) {
                $justif->setPrestataire(null);
            }
        }

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

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

    /**
     * @return Collection<int, Experience>
     */
    public function getExperiences(): Collection
    {
        return $this->experiences;
    }

    public function addExperience(Experience $experience): static
    {
        if (!$this->experiences->contains($experience)) {
            $this->experiences->add($experience);
            $experience->setPrestataire($this);
        }

        return $this;
    }

    public function removeExperience(Experience $experience): static
    {
        if ($this->experiences->removeElement($experience)) {
            // set the owning side to null (unless already changed)
            if ($experience->getPrestataire() === $this) {
                $experience->setPrestataire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notif>
     */
    public function getNotifs(): Collection
    {
        return $this->notifs;
    }

    public function addNotif(Notif $notif): static
    {
        if (!$this->notifs->contains($notif)) {
            $this->notifs->add($notif);
            $notif->setUser($this);
        }

        return $this;
    }

    public function removeNotif(Notif $notif): static
    {
        if ($this->notifs->removeElement($notif)) {
            // set the owning side to null (unless already changed)
            if ($notif->getUser() === $this) {
                $notif->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Postuler>
     */
    public function getPostulers(): Collection
    {
        return $this->postulers;
    }

    public function addPostuler(Postuler $postuler): static
    {
        if (!$this->postulers->contains($postuler)) {
            $this->postulers->add($postuler);
            $postuler->setPrestataire($this);
        }

        return $this;
    }

    public function removePostuler(Postuler $postuler): static
    {
        if ($this->postulers->removeElement($postuler)) {
            // set the owning side to null (unless already changed)
            if ($postuler->getPrestataire() === $this) {
                $postuler->setPrestataire(null);
            }
        }

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;

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
    /**
     * @return Collection<int, CommentPresta>
     */
    public function getCommentPrestas(): Collection
    {
        return $this->commentPrestas;
    }

    public function addCommentPresta(CommentPresta $commentPresta): static
    {
        if (!$this->commentPrestas->contains($commentPresta)) {
            $this->commentPrestas->add($commentPresta);
            $commentPresta->setPrestataire($this);
        }

        return $this;
    }

    public function removeCommentPresta(CommentPresta $commentPresta): static
    {
        if ($this->commentPrestas->removeElement($commentPresta)) {
            // set the owning side to null (unless already changed)
            if ($commentPresta->getPrestataire() === $this) {
                $commentPresta->setPrestataire(null);
            }
        }

        return $this;
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getIdStripe(): ?string
    {
        return $this->idStripe;
    }

    public function setIdStripe(?string $idStripe): static
    {
        $this->idStripe = $idStripe;

        return $this;
    }

    public function isStatutStripe(): ?bool
    {
        return $this->statutStripe;
    }

    public function setStatutStripe(?bool $statutStripe): static
    {
        $this->statutStripe = $statutStripe;

        return $this;
    }

    /**
     * @return Collection<int, Invit>
     */
    public function getInvits(): Collection
    {
        return $this->invits;
    }

    public function addInvit(Invit $invit): static
    {
        if (!$this->invits->contains($invit)) {
            $this->invits->add($invit);
            $invit->setHote($this);
        }

        return $this;
    }

    public function removeInvit(Invit $invit): static
    {
        if ($this->invits->removeElement($invit)) {
            // set the owning side to null (unless already changed)
            if ($invit->getHote() === $this) {
                $invit->setHote(null);
            }
        }

        return $this;
    }

    public function isVeryEmail(): ?bool
    {
        return $this->VeryEmail;
    }

    public function setVeryEmail(?bool $VeryEmail): static
    {
        $this->VeryEmail = $VeryEmail;

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }

    public function getPalier(): ?string
    {
        return $this->palier;
    }

    public function setPalier(?string $palier): static
    {
        $this->palier = $palier;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getRaisonSociale(): ?string
    {
        return $this->raison_sociale;
    }

    public function setRaisonSociale(?string $raison_sociale): static
    {
        $this->raison_sociale = $raison_sociale;

        return $this;
    }

    /**
     * @return Collection<int, DeviceToken>
     */
    public function getDeviceTokens(): Collection
    {
        return $this->deviceTokens;
    }

    public function addDeviceToken(DeviceToken $deviceToken): static
    {
        if (!$this->deviceTokens->contains($deviceToken)) {
            $this->deviceTokens->add($deviceToken);
            $deviceToken->setUser($this);
        }

        return $this;
    }

    public function removeDeviceToken(DeviceToken $deviceToken): static
    {
        if ($this->deviceTokens->removeElement($deviceToken)) {
            // set the owning side to null (unless already changed)
            if ($deviceToken->getUser() === $this) {
                $deviceToken->setUser(null);
            }
        }

        return $this;
    }
}
