<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\NotificationMethods;
use App\Enum\NotificationTimings;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    #[Assert\Length(
        min: 5,
        max: 25,
        minMessage: "Username must be at least 5 characters long",
        maxMessage: "Username cannot be longer than 25 characters"
    )]
    #[Assert\NotBlank(message: "Username is required")]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "In valid email")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: "Password must be at least 8 characters long",
        maxMessage: "Password cannot be longer than 20 characters"
    )]
    #[Assert\NotBlank(message: "Password is required")]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $modStatus = false;

    #[ORM\PrePersist]
    public function setDatetimeAutomatically(): void
    {
        // Automatically set the datetime to the current time when the entity is first persisted
        if ($this->creationDate === null) {
            $this->creationDate = new \DateTime();
        }
    }

    #[ORM\Column(name: 'creationDate', type: "datetime", nullable: true, columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP")]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'userId', cascade: ['persist', 'remove'])]
    private Collection $events;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private Collection $subscriptions;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "googleId cannot be longer than 255 characters"
    )]
    private ?string $googleId = null;


    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'followers', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinTable(name: 'followers_users')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[ORM\InverseJoinColumn(name: 'followed_user_id', referencedColumnName: 'id', nullable: false)]
    private Collection $usersBeingFollowed;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'usersBeingFollowed', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private Collection $followers;


    #[ORM\OneToMany(targetEntity: BookmarkedEvent::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $bookmarks;


    #[ORM\Column(name: 'wantsNotification',type: 'boolean', nullable: false)]
    #[Assert\Choice(choices: [true, false], message: "wantsNotification must be true or false")]
    private bool $wantsNotifications = false;

    #[ORM\Column(type: 'json')]
    #[Assert\All([
        new Assert\Choice(choices: [NotificationMethods::EMAIL], message: 'Invalid notification method.'),
    ])]
    private array $notificationMethods = [];

    #[ORM\Column(type: 'json')]
    #[Assert\All([
        new Assert\Choice(choices: [NotificationTimings::DAY0_BEFORE, NotificationTimings::DAY1_BEFORE, NotificationTimings::DAY7_BEFORE], message: 'Invalid notification time.'),
    ])]
    private array $notificationTimes = [];

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
        $this->eventInteractions = new ArrayCollection();
        $this->usersBeingFollowed = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->bookmarks = new ArrayCollection();
    }

    #[ORM\Column(type: "json")]
    private array $roles = []; // Default to an empty array

    public function setId(int $userId): self
    {
        $this->id = $userId;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }


    public function isModerator(): ?bool
    {
        return $this->modStatus;
    }

    public function setModerator(bool $modStatus): static
    {
        $this->modStatus = $modStatus;
        if ($modStatus) {
            if (!in_array('ROLE_MODERATOR', $this->roles, true)) {
                $this->roles[] = 'ROLE_MODERATOR';
            }
        } else {
            $this->roles = array_filter($this->roles, fn($role) => $role !== 'ROLE_MODERATOR');
        }
        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setUserId($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            // Set the owning side to null (unless already changed)
            if ($event->getUserId() === $this) {
                $event->setUserId(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        // Ensure the user always has at least a "ROLE_USER"
        $roles = $this->roles;

        if (empty($this->roles)) {
            $this->roles[] = 'REGISTERED';
        }

        return $roles;
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }


    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }


    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription))
        {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }
        return $this;
    }

    #[ORM\OneToOne(targetEntity: Banned::class, mappedBy: 'userId', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?Banned $banned = null;

    public function getBanned(): ?Banned
    {
        return $this->banned;
    }

    public function setBanned(?Banned $banned): static
    {
        $this->banned = $banned;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getWantsNotifications(): bool
    {
        return $this->wantsNotifications;
    }

    public function setWantsNotifications(bool $wantsNotifications): self
    {
        $this->wantsNotifications = $wantsNotifications;
        return $this;
    }

    public function getNotificationMethods(): array
    {
        return $this->notificationMethods;
    }

    public function setNotificationMethods(array $notificationMethods): self
    {
        // Ensure all values are valid enums before saving
        foreach ($notificationMethods as $method) {
            if (!$method instanceof NotificationMethods) {
                throw new \InvalidArgumentException("Invalid notification method: $method");
            }
        }

        // Store only the enum values as strings
        $this->notificationMethods = array_map(fn($method) => $method->value, $notificationMethods);

        return $this;
    }

    public function getNotificationTimes(): array
    {
        return $this->notificationTimes;
    }

    public function setNotificationTimes(array $notificationTimes): self
    {
        // Ensure all values are valid enums before saving
        foreach ($notificationTimes as $timing) {
            if (!$timing instanceof NotificationTimings) {
                throw new \InvalidArgumentException("Invalid notification method: $timing");
            }
        }

        // Store only the enum values as strings
        $this->notificationTimes = array_map(fn($timing) => $timing->value, $notificationTimes);

        return $this;
    }

    /**
     * Gets all the Users that this User is following.
     * @return Collection Users
     */
    public function getUsersBeingFollowed(): Collection
    {
        return $this->usersBeingFollowed;
    }


    /**
     * Makes this User follow the given User.
     * @param User $followedUser User that will be followed
     * @return $this User that now follows the given User
     */
    public function followUser(User $followedUser): static
    {
        if (!$this->usersBeingFollowed->contains($followedUser))
        {
            $this->usersBeingFollowed->add($followedUser);
            $followedUser->addToFollowersList($this);
        }

        return $this;
    }


    /**
     * Makes this User unfollow the given User, or does nothing if the User
     * is not currently following the given User.
     * @param User $followedUser User to unfollow
     * @return $this User that is not following the given User
     */
    public function unfollowUser(User $followedUser): static
    {
        $this->usersBeingFollowed->removeElement($followedUser);
        $followedUser->removeFromFollowersList($this);
        return $this;
    }


    /**
     * Gets all the Users that are following this User.
     * @return Collection Users
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }


    /**
     * Adds a given User to the list of Users that follow this User.
     * @param User $follower User that will follow the current User
     * @return $this User being followed by the given User
     */
    public function addToFollowersList(User $follower): static
    {
        if (!$this->followers->contains($follower))
        {
            $this->followers->add($follower);
        }
        return $this;
    }


    /**
     * Removes a given User from the list of Users that follow this User.
     * @param User $follower User that will unfollow the current User
     * @return $this User not being followed by the given User
     */
    public function removeFromFollowersList(User $follower): static
    {
        $this->followers->removeElement($follower);
        return $this;
    }


    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EventInteraction::class, orphanRemoval: true)]
    private Collection $eventInteractions;

    /**
     * @return Collection<int, EventInteraction>
     */
    public function getEventInteractions(): Collection
    {
        return $this->eventInteractions;
    }

    public function addEventInteraction(EventInteraction $eventInteraction): void
    {
        if (!$this->eventInteractions->contains($eventInteraction)) {
            $this->eventInteractions->add($eventInteraction);
            $eventInteraction->setUser($this);
        }
    }

    public function removeEventInteraction(EventInteraction $eventInteraction): void
    {
        if ($this->eventInteractions->removeElement($eventInteraction)) {
            // set the owning side to null (unless already changed)
            if ($eventInteraction->getUser() === $this) {
                $eventInteraction->setUser(null);
            }
        }
    }

    /**
     * Get all events this user is interested in
     *
     * @return Collection<int, Event>
     */
    public function getInterestedEvents(): Collection
    {
        return $this->eventInteractions
            ->filter(fn(EventInteraction $interaction) =>
                $interaction->getStatus() === EventInteractionStatus::INTERESTED)
            ->map(fn(EventInteraction $interaction) => $interaction->getEvent());
    }

    /**
     * Get all events this user is attending
     *
     * @return Collection<int, Event>
     */
    public function getAttendingEvents(): Collection
    {
        return $this->eventInteractions
            ->filter(fn(EventInteraction $interaction) =>
                $interaction->getStatus() === EventInteractionStatus::ATTENDING)
            ->map(fn(EventInteraction $interaction) => $interaction->getEvent());
    }

    /**
     * Check if user is interested in a specific event
     */
    public function isInterestedIn(Event $event): bool
    {
        foreach ($this->eventInteractions as $interaction) {
            if ($interaction->getEvent() === $event &&
                $interaction->getStatus() === EventInteractionStatus::INTERESTED) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user is attending a specific event
     */
    public function isAttending(Event $event): bool
    {
        foreach ($this->eventInteractions as $interaction) {
            if ($interaction->getEvent() === $event &&
                $interaction->getStatus() === EventInteractionStatus::ATTENDING) {
                return true;
            }
        }
        return false;
    }

}
