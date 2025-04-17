<?php

namespace App\Entity;

use App\Enum\EventInteractionStatus;
use App\Repository\EventInteractionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventInteractionRepository::class)]
#[ORM\Table(name: 'event_interactions')]
class EventInteraction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'userInteractions')]
    #[ORM\JoinColumn(nullable: false)]
    private Event $event;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'eventInteractions')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', enumType: EventInteractionStatus::class)]
    private EventInteractionStatus $status = EventInteractionStatus::NO_INTERACTION;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getStatus(): EventInteractionStatus
    {
        return $this->status;
    }

    public function setStatus(EventInteractionStatus $status): void
    {
        $this->status = $status;
    }
}