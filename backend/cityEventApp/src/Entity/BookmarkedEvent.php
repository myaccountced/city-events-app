<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BookmarkedEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookmarkedEventRepository::class)]
#[ApiResource]
class BookmarkedEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'bookmarks')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Event::class, cascade: ['persist'], inversedBy: 'bookmarks')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: false)]
    private ?Event $event = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getEventTitle(): ?string
    {
        return $this->event?->getEventTitle();
    }
    public function getEventStartDate(): ?\DateTimeInterface
    {
        return $this->event?->getEventStartDate();
    }
    public function getEventLink(): ?string
    {
        return $this->event?->getEventLink();
    }

}
