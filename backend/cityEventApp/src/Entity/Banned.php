<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BannedRepository;
use Doctrine\ORM\Mapping as ORM;;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;
#[ORM\Entity(repositoryClass: BannedRepository::class)]
#[ApiResource]
class Banned
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id',nullable: false, onDelete: "CASCADE")]
    private ?User $userId = null;

    #[ORM\Column(name: 'bannedDate', type: "datetime", columnDefinition: "TIMESTAMP DEFAULT CURRENT_TIMESTAMP", nullable: true)] //["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "Reason is required",
        maxMessage: "Reason must not exceed 255 characters"
    )]
    private ?string $reason = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?User
    {
        return $this->userId;
    }

    public function setUserId(User $user): static
    {
        $this->userId = $user;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(?\DateTimeInterface $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
