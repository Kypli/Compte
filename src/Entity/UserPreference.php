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

    #[ORM\Column(type: "string", length: 30, options: ["default" => "comma"])]
    private $moneyDisplayFormat = 'comma';

    #[ORM\Column(type: "string", length: 10, options: ["default" => "EUR"])]
    private $moneyCurrency = 'EUR';

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private $moneyTrimZeros = false;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $moneyShowZeroDecimals = true;

    #[ORM\Column(type: "string", length: 20, options: ["default" => "green"])]
    private $dashboardBackground = 'green';

    #[ORM\Column(type: "string", length: 20, options: ["default" => "green"])]
    private $accountBackground = 'green';

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showEditableBorder = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showTableTotals = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showTableMonthlyAverage = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showTablePercentage = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showBalanceTable = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showBalanceCumulative = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showAnnualGain = true;

    #[ORM\Column(type: "boolean", options: ["default" => true])]
    private $showSubCategories = true;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private $mergeIncomeExpenseTables = false;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private $accountTutorialSeen = false;

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

    public function getMoneyDisplayFormat(): string
    {
        return $this->moneyDisplayFormat;
    }

    public function setMoneyDisplayFormat(string $moneyDisplayFormat): self
    {
        $legacyFormats = [
            'us_dollar' => 'dot',
            'uk_pound' => 'dot',
            'swiss_franc' => 'dot',
            'german_euro' => 'german',
        ];
        $moneyDisplayFormat = $legacyFormats[$moneyDisplayFormat] ?? $moneyDisplayFormat;
        $this->moneyDisplayFormat = in_array($moneyDisplayFormat, ['dot', 'comma', 'euro_cents', 'german'], true)
            ? $moneyDisplayFormat
            : 'comma'
        ;

        return $this;
    }

    public function getMoneyCurrency(): string
    {
        return $this->moneyCurrency;
    }

    public function setMoneyCurrency(string $moneyCurrency): self
    {
        $moneyCurrency = strtoupper($moneyCurrency);
        $this->moneyCurrency = in_array($moneyCurrency, ['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'CAD'], true)
            ? $moneyCurrency
            : 'EUR'
        ;
        if ('JPY' === $this->moneyCurrency && !$this->moneyTrimZeros) {
            $this->moneyShowZeroDecimals = false;
        }

        return $this;
    }

    public function isMoneyTrimZeros(): bool
    {
        return $this->moneyTrimZeros;
    }

    public function setMoneyTrimZeros(bool $moneyTrimZeros): self
    {
        $this->moneyTrimZeros = $moneyTrimZeros;
        if ('JPY' === $this->moneyCurrency && !$this->moneyTrimZeros) {
            $this->moneyShowZeroDecimals = false;
        }

        return $this;
    }

    public function isMoneyShowZeroDecimals(): bool
    {
        return $this->moneyShowZeroDecimals;
    }

    public function setMoneyShowZeroDecimals(bool $moneyShowZeroDecimals): self
    {
        $this->moneyShowZeroDecimals = 'JPY' === $this->moneyCurrency && !$this->moneyTrimZeros
            ? false
            : $moneyShowZeroDecimals
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

    public function isShowTableTotals(): bool
    {
        return $this->showTableTotals;
    }

    public function setShowTableTotals(bool $showTableTotals): self
    {
        $this->showTableTotals = $showTableTotals;

        return $this;
    }

    public function isShowTableMonthlyAverage(): bool
    {
        return $this->showTableMonthlyAverage;
    }

    public function setShowTableMonthlyAverage(bool $showTableMonthlyAverage): self
    {
        $this->showTableMonthlyAverage = $showTableMonthlyAverage;

        return $this;
    }

    public function isShowTablePercentage(): bool
    {
        return $this->showTablePercentage;
    }

    public function setShowTablePercentage(bool $showTablePercentage): self
    {
        $this->showTablePercentage = $showTablePercentage;

        return $this;
    }

    public function isShowBalanceTable(): bool
    {
        return $this->showBalanceTable;
    }

    public function setShowBalanceTable(bool $showBalanceTable): self
    {
        $this->showBalanceTable = $showBalanceTable;

        return $this;
    }

    public function isShowBalanceCumulative(): bool
    {
        return $this->showBalanceCumulative;
    }

    public function setShowBalanceCumulative(bool $showBalanceCumulative): self
    {
        $this->showBalanceCumulative = $showBalanceCumulative;

        return $this;
    }

    public function isShowAnnualGain(): bool
    {
        return $this->showAnnualGain;
    }

    public function setShowAnnualGain(bool $showAnnualGain): self
    {
        $this->showAnnualGain = $showAnnualGain;

        return $this;
    }

    public function isShowSubCategories(): bool
    {
        return $this->showSubCategories;
    }

    public function setShowSubCategories(bool $showSubCategories): self
    {
        $this->showSubCategories = $showSubCategories;

        return $this;
    }

    public function isMergeIncomeExpenseTables(): bool
    {
        return $this->mergeIncomeExpenseTables;
    }

    public function setMergeIncomeExpenseTables(bool $mergeIncomeExpenseTables): self
    {
        $this->mergeIncomeExpenseTables = $mergeIncomeExpenseTables;

        return $this;
    }

    public function isAccountTutorialSeen(): bool
    {
        return $this->accountTutorialSeen;
    }

    public function setAccountTutorialSeen(bool $accountTutorialSeen): self
    {
        $this->accountTutorialSeen = $accountTutorialSeen;

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
