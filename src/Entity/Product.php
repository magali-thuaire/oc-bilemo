<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use App\Api\Attribute\Link;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Timestampable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Annotation;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'product.name.unique')]
#[Link(
    name: 'self',
    route: 'api_products_show',
    params: ['id' => 'object.getId()']
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Annotation\Groups(['product:read'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Annotation\Groups(['product:read'])]
    private $name;

    #[ORM\Column(type: 'text')]
    #[Annotation\Groups(['product:read'])]
    private $description;

    #[ORM\Column(type: 'float')]
    #[Annotation\Groups(['product:read'])]
    private $price;

    #[Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $createdAt;

    #[Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $updatedAt;

    private const DATETIME_FORMAT = 'd/m/Y H:i:s';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt->format(self::DATETIME_FORMAT);
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt->format(self::DATETIME_FORMAT);

    }
}
