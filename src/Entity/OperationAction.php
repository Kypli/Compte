<?php

namespace App\Entity;

use App\Repository\OperationActionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationActionRepository::class)]
class OperationAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Operation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Operation $operation = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\Column(type: 'string', length: 15)]
    private string $actionType;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $actionAt;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $beforeSnapshot = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $afterSnapshot = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $undoSnapshot = null;

    #[ORM\Column(type: 'boolean')]
    private bool $cancelled = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): self
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getActionAt(): \DateTimeInterface
    {
        return $this->actionAt;
    }

    public function setActionAt(\DateTimeInterface $actionAt): self
    {
        $this->actionAt = $actionAt;

        return $this;
    }

    public function getBeforeSnapshot(): ?array
    {
        return $this->beforeSnapshot;
    }

    public function setBeforeSnapshot(?array $beforeSnapshot): self
    {
        $this->beforeSnapshot = $beforeSnapshot;

        return $this;
    }

    public function getAfterSnapshot(): ?array
    {
        return $this->afterSnapshot;
    }

    public function setAfterSnapshot(?array $afterSnapshot): self
    {
        $this->afterSnapshot = $afterSnapshot;

        return $this;
    }

    public function getUndoSnapshot(): ?array
    {
        return $this->undoSnapshot;
    }

    public function setUndoSnapshot(?array $undoSnapshot): self
    {
        $this->undoSnapshot = $undoSnapshot;

        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): self
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    public function isUndoable(): bool
    {
        if ($this->cancelled){
            return null !== $this->undoSnapshot;
        }

        return 'create' === $this->actionType || null !== $this->beforeSnapshot;
    }

    public function isCategoryMove(): bool
    {
        return 'move' === $this->actionType
            && null !== $this->category
            && 'subcategory' !== ($this->beforeSnapshot['scope'] ?? null)
        ;
    }

    public function isSubCategoryMove(): bool
    {
        return 'move' === $this->actionType
            && null !== $this->category
            && 'subcategory' === ($this->beforeSnapshot['scope'] ?? null)
        ;
    }

    public function getDisplayNumber(): float
    {
        $snapshot = $this->afterSnapshot ?? $this->beforeSnapshot;

        return isset($snapshot['number'])
            ? (float) $snapshot['number']
            : (float) ($this->operation?->getNumber() ?? 0)
        ;
    }

    public function getDisplayDate(): \DateTimeInterface
    {
        $snapshot = $this->afterSnapshot ?? $this->beforeSnapshot;

        return isset($snapshot['date'])
            ? new \DateTime($snapshot['date'])
            : ($this->operation?->getDate() ?? $this->actionAt)
        ;
    }
}
