<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OperationRepository::class)
 */
#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    #[ORM\Column(type: "float")]
    private $number;

    /**
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: "boolean")]
    private $anticipe = false;

    /**
     * @ORM\Column(type="datetime")
     */
    #[ORM\Column(type: "datetime")]
    private $date;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: "text", nullable: true)]
    private $comment;

    /**
     * @ORM\Column(type="string", length=15)
     */
    #[ORM\Column(type: "string", length: 15)]
    private $lastAction;

    /**
     * @ORM\Column(type="datetime")
     */
    #[ORM\Column(type: "datetime")]
    private $dateLastAction;

    /**
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: "boolean")]
    private $actif = 1;

    #[ORM\Column(type: "boolean")]
    private bool $anomalyIgnored = false;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $anomalyIgnoredUntil = null;

    /**
     * @ORM\ManyToOne(targetEntity=SubCategory::class, inversedBy="operations")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: SubCategory::class, inversedBy: "operations")]
    #[ORM\JoinColumn(nullable: false)]
    private $subcategory;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $assignee = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?float
    {
        return $this->number;
    }

    public function setNumber(float $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function isAnticipe(): ?bool
    {
        return $this->anticipe;
    }

    public function setAnticipe(bool $anticipe): self
    {
        $this->anticipe = $anticipe;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getLastAction(): ?string
    {
        return $this->lastAction;
    }

    public function setLastAction(string $lastAction): self
    {
        $this->lastAction = $lastAction;

        return $this;
    }

    public function getDateLastAction(): ?\DateTimeInterface
    {
        return $this->dateLastAction;
    }

    public function setDateLastAction(\DateTimeInterface $dateLastAction): self
    {
        $this->dateLastAction = $dateLastAction;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function isAnomalyIgnored(): bool
    {
        return $this->anomalyIgnored;
    }

    public function setAnomalyIgnored(bool $anomalyIgnored): self
    {
        $this->anomalyIgnored = $anomalyIgnored;

        return $this;
    }

    public function getAnomalyIgnoredUntil(): ?\DateTimeInterface
    {
        return $this->anomalyIgnoredUntil;
    }

    public function setAnomalyIgnoredUntil(?\DateTimeInterface $anomalyIgnoredUntil): self
    {
        $this->anomalyIgnoredUntil = $anomalyIgnoredUntil;

        return $this;
    }

    public function getSubcategory(): ?SubCategory
    {
        return $this->subcategory;
    }

    public function setSubcategory(?SubCategory $subcategory): self
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): self
    {
        $this->assignee = $assignee;

        return $this;
    }

    public function hasSubCategory(Operation $ope, SubCategory $sc): Bool
    {
        if ($ope->getSubCategory() == $sc){
            return true;
        }

        return false;
    }
}
