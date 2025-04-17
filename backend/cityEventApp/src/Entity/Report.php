<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "EventID is required")]
    #[Assert\Type(type: "integer", message: "Event ID must be a valid number")]
    private ?int $eventID = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $reportDate;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $reportTime;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 1,
        max: 255,
        minMessage: "Reason is required",
        maxMessage: "Reason must not exceed 255 characters"
    )]
    private ?string $reason = null;

    public function __construct()
    {
        // Set the reportDate and reportTime to the current time when a Report object is created
        $this->reportDate = new \DateTime();
        $this->reportTime = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventID(): ?int
    {
        return $this->eventID;
    }

    public function setEventID(int $eventID): static
    {
        $this->eventID = $eventID;

        return $this;
    }

    public function getReportDate(): ?\DateTimeInterface
    {
        return $this->reportDate;
    }

    public function setReportDate(\DateTimeInterface $reportDate): static
    {
        $this->reportDate = $reportDate;

        return $this;
    }

    public function getReportTime(): ?\DateTimeInterface
    {
        return $this->reportTime;
    }
    public function setReportTime(\DateTimeInterface $reportTime): static
    {
        $this->reportTime = $reportTime;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $Reason): static
    {
        $this->reason = $Reason;

        return $this;
    }
}
