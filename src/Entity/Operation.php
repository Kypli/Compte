<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OperationRepository::class)
 */
class Operation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float")
     */
    private $number;

    /**
     * @ORM\Column(type="boolean")
     */
    private $anticipe = false;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=SubCategory::class, inversedBy="operations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $subcategory;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateLastAction;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $lastAction;

    /**
     * @ORM\Column(type="boolean")
     */
    private $actif = 1;

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

    public function getSubcategory(): ?SubCategory
    {
        return $this->subcategory;
    }

    public function setSubcategory(?SubCategory $subcategory): self
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    public function hasSubCategory(Operation $ope, SubCategory $sc): Bool
    {
        if ($ope->getSubCategory() == $sc){
            return true;
        }

        return false;
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

    public function getLastAction(): ?string
    {
        return $this->lastAction;
    }

    public function setLastAction(string $lastAction): self
    {
        $this->lastAction = $lastAction;

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
}
