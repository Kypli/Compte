<?php

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserPreferenceRepository::class)
 */
class UserPreference
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $compteGenreShow = true;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="preferences", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isCompteGenreShow(): ?bool
    {
        return $this->compteGenreShow;
    }

    public function setCompteGenreShow(bool $compteGenreShow): self
    {
        $this->compteGenreShow = $compteGenreShow;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
