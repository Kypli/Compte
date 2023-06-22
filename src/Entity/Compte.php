<?php

namespace App\Entity;

use App\Repository\CompteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompteRepository::class)
 */
class Compte
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=35)
     */
    private $libelle;

    /**
     * @ORM\Column(type="boolean")
     */
    private $main;

    /**
     * @ORM\Column(type="integer", length=10)
     */
    private $decouvert = 0;

    /**
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="compte", orphanRemoval=true)
     */
    private $categories;

    /**
     * @ORM\ManyToOne(targetEntity=CompteType::class, inversedBy="comptes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="comptes")
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->categories = new ArrayCollection();
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

    public function getMain(): ?bool
    {
        return $this->main;
    }

    public function setMain(bool $main): self
    {
        $this->main = $main;

        return $this;
    }

    public function getDecouvert(): ?int
    {
        return $this->decouvert;
    }

    public function setDecouvert(int $decouvert): self
    {
        $this->decouvert = $decouvert;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->setCompte($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getCompte() === $this) {
                $category->setCompte(null);
            }
        }

        return $this;
    }

    public function getType(): ?CompteType
    {
        return $this->type;
    }

    public function setType(?CompteType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);

        return $this;
    }
}
