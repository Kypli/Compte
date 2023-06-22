<?php

namespace App\Entity;

use App\Repository\CompteTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompteTypeRepository::class)
 */
class CompteType
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $libelle;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $libelleShort;

    /**
     * @ORM\Column(type="boolean")
     */
    private $decouvert;

    /**
     * @ORM\Column(type="float")
     */
    private $tauxInteret;

    /**
     * @ORM\Column(type="integer")
     */
    private $plancher;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $plafond;

    /**
     * @ORM\OneToMany(targetEntity=Compte::class, mappedBy="type")
     */
    private $comptes;

    public function __construct()
    {
        $this->comptes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getLibelleShort(): ?string
    {
        return $this->libelleShort;
    }

    public function setLibelleShort(?string $libelleShort): self
    {
        $this->libelleShort = $libelleShort;

        return $this;
    }

    public function isDecouvert(): ?bool
    {
        return $this->decouvert;
    }

    public function setDecouvert(bool $decouvert): self
    {
        $this->decouvert = $decouvert;

        return $this;
    }

    public function getTauxInteret(): ?float
    {
        return $this->tauxInteret;
    }

    public function setTauxInteret(float $tauxInteret): self
    {
        $this->tauxInteret = $tauxInteret;

        return $this;
    }

    public function getPlancher(): ?int
    {
        return $this->plancher;
    }

    public function setPlancher(int $plancher): self
    {
        $this->plancher = $plancher;

        return $this;
    }

    public function getPlafond(): ?int
    {
        return $this->plafond;
    }

    public function setPlafond(?int $plafond): self
    {
        $this->plafond = $plafond;

        return $this;
    }

    /**
     * @return Collection<int, Compte>
     */
    public function getComptes(): Collection
    {
        return $this->comptes;
    }

    public function addCompte(Compte $compte): self
    {
        if (!$this->comptes->contains($compte)) {
            $this->comptes[] = $compte;
            $compte->setType($this);
        }

        return $this;
    }

    public function removeCompte(Compte $compte): self
    {
        if ($this->comptes->removeElement($compte)) {
            // set the owning side to null (unless already changed)
            if ($compte->getType() === $this) {
                $compte->setType(null);
            }
        }

        return $this;
    }
}
