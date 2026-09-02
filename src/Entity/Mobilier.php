<?php

namespace App\Entity;

use App\Repository\MobilierRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MobilierRepository::class)
 */
#[ORM\Entity(repositoryClass: MobilierRepository::class)]
class Mobilier
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
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $user = null;

    /**
     * @ORM\Column(type="string", length=160)
     */
    #[ORM\Column(type: "string", length: 160)]
    private string $libelle = '';

    /**
     * @ORM\Column(type="float")
     */
    #[ORM\Column(type: "float")]
    private float $valeur = 0.0;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    #[ORM\Column(type: "string", length: 80, nullable: true)]
    private ?string $categorie = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getValeur(): ?float
    {
        return $this->valeur;
    }

    public function setValeur(float $valeur): self
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
