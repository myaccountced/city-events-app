<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\RecurringType;
use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ApiResource]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(nullable:true)]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "You must enter in a title for the event.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s]+$/",
        message: "The event title cannot contain numbers or special characters."
    )]
    private ?string $eventTitle = null;

    #[ORM\Column(length: 1000)]
    #[Assert\NotBlank(message: "You must provide a description for the event.")]
    #[Assert\Length(
        min: 10,
        max: 250,
        minMessage: "The description must be between 10 and 250 characters long.",
        maxMessage: "The description must be between 10 and 250 characters long."
    )]
    private ?string $eventDescription = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "You must enter in a city for the event.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s]+$/",
        message: "You must enter in a valid city name for the event."
    )]
    private ?string $eventLocation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "You must enter a start date.")]
    #[Assert\GreaterThan(
        "today",
        message: "You must enter a start date that is after today."
    )]
    private ?\DateTimeInterface $eventStartDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Assert\Expression(
        "this.getEventEndDate() === null || this.getEventEndDate() >= this.getEventStartDate()",
        message: "The end date must come after the start date."
    )]
    #[Assert\GreaterThan(
        "today",
        message: "You must enter an end date that is after today."
    )]
    private ?\DateTimeInterface $eventEndDate = null;


    // TODO: since this will eventually be a User object, this column will change from int to User
    #[ORM\Column(length: 255)]
    private ?string $eventCreator = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "At least one audience must be selected.")]
    private ?string $eventAudience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $eventLink = null;

    #[ORM\Column(length: 1255, nullable: true)]
    private ?string $eventImages = null;

    #[ORM\Column(nullable: true)]
    private ?bool $moderatorApproval = null;

    #[ORM\Column(nullable: true)]
    private ?int $reportCount = 0;

    #[ORM\Column(nullable: true)]
    private ?int $parentEventID = null;

    #[ORM\Column(nullable: true)]
    private ?RecurringType $eventRecurringType = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "The creation date is required.")]
    private ?\DateTimeInterface $eventCreationDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "The modification date is required.")]
    private ?\DateTimeInterface $eventModificationDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setEventID(int $eventID): static
    {
        $this->id = $eventID;
        return $this;
    }

    public function getEventTitle(): ?string
    {
        return $this->eventTitle;
    }

    public function setEventTitle(string $eventTitle): static
    {
        $this->eventTitle = $eventTitle;
        return $this;
    }

    public function getEventDescription(): ?string
    {
        return $this->eventDescription;
    }

    public function setEventDescription(string $eventDescription): static
    {
        $this->eventDescription = $eventDescription;
        return $this;
    }

    public function getEventLocation(): ?string
    {
        return $this->eventLocation;
    }

    public function setEventLocation(string $eventLocation): static
    {
        $this->eventLocation = $eventLocation;
        return $this;
    }

    public function getEventStartDate(): ?\DateTimeInterface
    {
        return $this->eventStartDate;
    }

    public function setEventStartDate(\DateTimeInterface $eventStartDate): static
    {
        $this->eventStartDate = $eventStartDate;
        return $this;
    }

    public function getEventEndDate(): ?\DateTimeInterface
    {
        return $this->eventEndDate;
    }

    public function setEventEndDate(?\DateTimeInterface $eventEndDate): static
    {
        $this->eventEndDate = $eventEndDate;
        return $this;
    }

    public function getEventCreator(): ?string
    {
        return $this->eventCreator;
    }

    public function setEventCreator(string $eventCreator): static
    {
        $this->eventCreator = $eventCreator;
        return $this;
    }

    public function getEventAudience(): ?string
    {
        return $this->eventAudience;
    }

    public function setEventAudience(string $eventAudience): static
    {
        $this->eventAudience = $eventAudience;
        return $this;
    }

    public function getEventLink(): ?string
    {
        return $this->eventLink;
    }


    public function setEventLink(?string $eventLink): static
    {
        $this->eventLink = $eventLink;
        return $this;
    }

    public function getEventImages(): ?string
    {
        return $this->eventImages;
    }

    public function setEventImages(?string $eventImages): static
    {
        $this->eventImages = $eventImages;
        return $this;
    }

    public function isModeratorApproval(): ?bool
    {
        return $this->moderatorApproval;
    }

    public function setModeratorApproval(?bool $moderatorApproval): static
    {
        $this->moderatorApproval = $moderatorApproval;
        return $this;
    }

    public function getParentEventID(): ?int
    {
        return $this->parentEventID;
    }

    public function setParentEventID(?int $parentEventID): static
    {
        $this->parentEventID = $parentEventID;
        return $this;
    }

    public function getEventRecurringType(): ?RecurringType
    {
        return $this->eventRecurringType;
    }

    public function setEventRecurringType(?RecurringType $eventRecurringType): static
    {
        $this->eventRecurringType = $eventRecurringType;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    private Collection $media;

    #[ORM\OneToMany(targetEntity: BookmarkedEvent::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    private Collection $bookmarks;

    public function __construct()
    {
        $this->media = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->bookmarks = new ArrayCollection();
        $this->userInteractions = new ArrayCollection();

        // These auto-set them on creation, but they CAN be set to otherwise!
        $this->eventCreationDate = new \DateTime();
        $this->eventModificationDate = $this->eventCreationDate;
    }

    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->media->contains($media)) {
            $this->media->add($media);
            $media->setEvent($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        if ($this->media->removeElement($media)) {
            // Set the owning side to null (unless already changed)
            if ($media->getEvent() === $this) {
                $media->setEvent(null);
            }
        }
        return $this;
    }


    public function getBookmarks(): Collection
    {
        return $this->bookmarks;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?User $userId = null;

    public function getUserId(): ?User
    {
        return $this->userId;
    }

    public function setUserId(?User $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getReportCount(): ?int
    {
        return $this->reportCount;
    }

    public function setReportCount(int $count): static
    {
        if ($this->reportCount < 3) {
            $this->reportCount = $count;
        }

        return $this;
    }

    public function clearReportCount(): static
    {
        $this->reportCount = 0;
        return $this;
    }


    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: 'event', cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[Assert\Count(min: 1, minMessage: "At least one category must be selected.")]
    private Collection $categories;


    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
//        if (!$this->getCategories()->contains($category))
//        {
//            $this->categories->add($category);
//            $category->setEvent($this);
//        }
//
//        return $this;

        // Check if a category with the same name already exists
        $categoryExists = false;
        foreach ($this->categories as $existingCategory) {
            if ($existingCategory->getCategoryName() === $category->getCategoryName()) {
                $categoryExists = true;
                break;
            }
        }

        if (!$categoryExists) {
            $this->categories->add($category);
            $category->setEvent($this);
        }

        return $this;
    }

    public function clearCategories(): void
    {
        // Create a copy of categories to avoid modification during iteration
        $categoriesToRemove = $this->categories->toArray();

        foreach ($categoriesToRemove as $category) {
            $this->categories->removeElement($category);
        }
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
            // Set the owning side to null if it's not already done
            if ($category->getEvent() === $this) {
                $category->setEvent(null);
            }
        }

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->eventCreationDate;
    }

    public function setCreationDate(\DateTimeInterface $eventCreationDate): static
    {
        $this->eventCreationDate = $eventCreationDate;
        return $this;
    }

    public function getModificationDate(): ?\DateTimeInterface
    {
        return $this->eventModificationDate;
    }

    public function setModificationDate(\DateTimeInterface $eventModificationDate): static
    {
        $this->eventModificationDate = $eventModificationDate;
        return $this;
    }


    /**
     * Creates an array that can act as the payload for a JSON response.
     * @return array key-values array representation of this event
     */
    public function toArrayOfProperties(): array
    {
        $cats = [];
        foreach ($this->categories->toArray() as $category) {
            $cats[] = $category->getCategoryName();
        }

        $imgs = [];
        foreach ($this->media as $media) {
            $imgs[] = $media->getPath();
        }

        $payload = [
            'id' => $this->getId(),
            'title' => $this->getEventTitle(),
            'description' => $this->getEventDescription(),
            'startDate' => $this->getEventStartDate()->format('Y-m-d'),
//            'endDate' => $this->getEventEndDate()?->format('Y-m-d'),
            'location' => $this->getEventLocation(),
            'audience' => $this->getEventAudience(),
            'category' => $cats,
            'images' => $imgs,
            'startTime' => $this->getEventStartDate()->format('H:i'),
//            'endTime' => $this->getEventEndDate()?->format('H:i'),
            'links' => $this->getEventLink(),
            'creator' => $this->getEventCreator(),
            'moderatorApproval' => $this->isModeratorApproval(),
            'recurringType' => $this->getEventRecurringType(),
        ];

        if ($this->eventEndDate != null) {
            $payload['endDate'] = $this->eventEndDate->format('Y-m-d');
            $payload['endTime'] = $this->eventEndDate->format('H:i');
        }

        return $payload;
    }

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventInteraction::class, orphanRemoval: true)]
    private Collection $userInteractions;

    /**
     * @return Collection<int, EventInteraction>
     */
    public function getUserInteractions(): Collection
    {
        return $this->userInteractions;
    }

    public function addUserInteraction(EventInteraction $userInteraction): void
    {
        if (!$this->userInteractions->contains($userInteraction)) {
            $this->userInteractions->add($userInteraction);
            $userInteraction->setEvent($this);
        }
    }

    public function removeUserInteraction(EventInteraction $userInteraction): void
    {
        if ($this->userInteractions->removeElement($userInteraction)) {
            // set the owning side to null (unless already changed)
            if ($userInteraction->getEvent() === $this) {
                $userInteraction->setEvent(null);
            }
        }
    }

    /**
     * Get users interested in this event
     *
     * @return Collection<int, User>
     */
    public function getInterestedUsers(): Collection
    {
        return $this->userInteractions
            ->filter(fn(EventInteraction $interaction) =>
                $interaction->getStatus() === EventInteractionStatus::INTERESTED)
            ->map(fn(EventInteraction $interaction) => $interaction->getUser());
    }

    /**
     * Get users attending this event
     *
     * @return Collection<int, User>
     */
    public function getAttendingUsers(): Collection
    {
        return $this->userInteractions
            ->filter(fn(EventInteraction $interaction) =>
                $interaction->getStatus() === EventInteractionStatus::ATTENDING)
            ->map(fn(EventInteraction $interaction) => $interaction->getUser());
    }

    /**
     * Get count of interested users
     */
    public function getInterestedCount(): int
    {
        return $this->getInterestedUsers()->count();
    }

    /**
     * Get count of attending users
     */
    public function getAttendingCount(): int
    {
        return $this->getAttendingUsers()->count();
    }

}
