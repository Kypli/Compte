<?php

namespace App\Entity;

use App\Repository\CompteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CompteRepository::class)
 */
#[ORM\Entity(repositoryClass: CompteRepository::class)]
class Compte
{
    public const MAX_ASSOCIATED_USERS = 3;

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
     * @ORM\Column(type="string", length=35)
     */
    #[ORM\Column(type: "string", length: 35)]
    private $libelle;

    /**
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: "boolean")]
    private $main;

    /**
     * @ORM\Column(type="integer", length=10)
     */
    #[ORM\Column(type: "integer", length: 10)]
    private $decouvert = 0;

    /**
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="compte", orphanRemoval=true)
     */
    #[ORM\OneToMany(targetEntity: Category::class, mappedBy: "compte", orphanRemoval: true)]
    private $categories;

    /**
     * @ORM\ManyToOne(targetEntity=CompteType::class, inversedBy="comptes")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity: CompteType::class, inversedBy: "comptes")]
    #[ORM\JoinColumn(nullable: false)]
    private $type;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="comptes")
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: "comptes")]
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $owner = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    #[ORM\Column(type: "json", nullable: true)]
    private array $userRoles = [];

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
        if (null === $this->owner) {
            $this->owner = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->users->removeElement($user);
        if (null !== $user->getId()) {
            unset($this->userRoles[(string) $user->getId()]);
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        if (null !== $this->owner) {
            return $this->owner;
        }

        $firstUser = $this->users->first();

        return false === $firstUser ? null : $firstUser;
    }

    public function setOwner(User $owner): self
    {
        $this->addUser($owner);
        $this->owner = $owner;

        return $this;
    }

    public function isUserOwner(User $user): bool
    {
        $owner = $this->getOwner();
        if (null === $owner) {
            return false;
        }

        return $owner === $user || (
            null !== $owner->getId()
            && null !== $user->getId()
            && $owner->getId() === $user->getId()
        );
    }

    public function getUserAccessRole(User $user): string
    {
        if (null === $user->getId()) {
            return 'editor';
        }

        $sharing = $this->userRoles[(string) $user->getId()] ?? null;
        if (is_array($sharing)) {
            return in_array($sharing['access'] ?? null, ['none', 'observer', 'editor'], true)
                ? $sharing['access']
                : 'editor';
        }

        return in_array($sharing, ['none', 'observer'], true) ? $sharing : 'editor';
    }

    public function isUserParticipant(User $user): bool
    {
        if (null === $user->getId()) {
            return false;
        }

        $sharing = $this->userRoles[(string) $user->getId()] ?? null;
        if (is_array($sharing)) {
            return (bool) ($sharing['participant'] ?? false);
        }

        return 'participant' === $sharing;
    }

    public function setUserSharing(User $user, string $access, bool $participant): self
    {
        if (!in_array($access, ['none', 'observer', 'editor'], true)) {
            throw new \InvalidArgumentException('Droit d’accès au compte invalide.');
        }
        if (null !== $user->getId()) {
            $this->userRoles[(string) $user->getId()] = [
                'access' => $access,
                'participant' => $participant,
            ];
        }

        return $this;
    }

    public function getUserAccessRoleLabel(User $user): string
    {
        return match ($this->getUserAccessRole($user)) {
            'none' => 'Aucun accès',
            'observer' => 'Observateur',
            default => 'Éditeur',
        };
    }
}
