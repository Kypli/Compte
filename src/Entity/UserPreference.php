<?php

namespace App\Entity;

use App\Repository\UserPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserPreferenceRepository::class)
 */
#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
class UserPreference
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
     * @ORM\Column(type="boolean")
     */
    #[ORM\Column(type: "boolean")]
    private $compteGenreShow = true;

    #[ORM\Column(type: "string", length: 20, options: ["default" => "classic"])]
    private $tablePalette = 'classic';

    #[ORM\Column(type: "string", length: 20, options: ["default" => "green"])]
    private $dashboardBackground = 'green';

    #[ORM\Column(type: "string", length: 20, options: ["default" => "green"])]
    private $accountBackground = 'green';

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showEditableBorder = true;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="preferences", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: "preferences", cascade: ["persist", "remove"])]
    #[ORM\JoinColumn(nullable: false)]
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

    public function getTablePalette(): string
    {
        return $this->tablePalette;
    }

    public function setTablePalette(string $tablePalette): self
    {
        $this->tablePalette = in_array($tablePalette, ['classic', 'soft', 'contrast', 'lagoon', 'berry', 'copper'], true)
            ? $tablePalette
            : 'classic'
        ;

        return $this;
    }

    public function getDashboardBackground(): string
    {
        return $this->dashboardBackground;
    }

    public function setDashboardBackground(string $dashboardBackground): self
    {
        $this->dashboardBackground = $this->normalizeBackground($dashboardBackground);

        return $this;
    }

    public function getAccountBackground(): string
    {
        return $this->accountBackground;
    }

    public function setAccountBackground(string $accountBackground): self
    {
        $this->accountBackground = $this->normalizeBackground($accountBackground);

        return $this;
    }

    public function isShowEditableBorder(): bool
    {
        return $this->showEditableBorder;
    }

    public function setShowEditableBorder(bool $showEditableBorder): self
    {
        $this->showEditableBorder = $showEditableBorder;

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

    private function normalizeBackground(string $background): string
    {
        return in_array($background, ['green', 'light', 'grey'], true)
            ? $background
            : 'green'
        ;
    }
}
