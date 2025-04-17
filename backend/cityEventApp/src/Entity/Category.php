<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Nullable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(nullable:true)]
    private ?int $id = null;


    #[ORM\Column(nullable:false)]
    private ?string $categoryName = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }


    public function setCategoryName(string $categoryName): static
    {
        $validCategories = [
            'Arts and Culture', 'Education', 'Health and Wellness',
            'Food and Drink', 'Music', 'Nature and Outdoors',
            'Sports', 'Technology', 'Others'
        ];

        if (!in_array($categoryName, $validCategories)) {
            $this->categoryName = 'Others';  // Default value
        } else {
            $this->categoryName = $categoryName;
        }

        return $this;
    }


    public function getEvent(): ?Event
    {
        return $this->event;
    }


    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }


    /**
     * Gets all valid category names.
     * @return string[] array of all valid category names
     */
    public static function getAllCategories(): array
    {
        return [
            'Arts and Culture', 'Education', 'Health and Wellness',
            'Food and Drink', 'Music', 'Nature and Outdoors',
            'Sports', 'Technology', 'Others'
        ];
    }

}
