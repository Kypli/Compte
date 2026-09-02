<?php

namespace App\Entity;

use App\Repository\CreditRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CreditRepository::class)]
class Credit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?User $user = null;

    #[ORM\Column(type: "string", length: 160)]
    private string $libelle = '';

    #[ORM\Column(type: "string", length: 120, nullable: true)]
    private ?string $organisme = null;

    #[ORM\Column(type: "string", length: 30)]
    private string $type = 'autre';

    #[ORM\Column(type: "float")]
    private float $montantInitial = 0.0;

    #[ORM\Column(type: "float")]
    private float $capitalRestant = 0.0;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $tauxAnnuel = null;

    #[ORM\Column(type: "float")]
    private float $mensualite = 0.0;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $assuranceMensuelle = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: "date", nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: "boolean")]
    private bool $actif = true;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $notes = null;

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

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getOrganisme(): ?string
    {
        return $this->organisme;
    }

    public function setOrganisme(?string $organisme): self
    {
        $this->organisme = $organisme;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return [
            'immobilier' => 'Immobilier',
            'automobile' => 'Automobile',
            'personnel' => 'Personnel',
            'travaux' => 'Travaux',
            'renouvelable' => 'Renouvelable',
            'etudiant' => 'Étudiant',
            'autre' => 'Autre',
        ][$this->type] ?? 'Autre';
    }

    public function getMontantInitial(): float
    {
        return $this->montantInitial;
    }

    public function setMontantInitial(float $montantInitial): self
    {
        $this->montantInitial = $montantInitial;

        return $this;
    }

    public function getCapitalRestant(): float
    {
        return $this->capitalRestant;
    }

    public function setCapitalRestant(float $capitalRestant): self
    {
        $this->capitalRestant = $capitalRestant;

        return $this;
    }

    public function getTauxAnnuel(): ?float
    {
        return $this->tauxAnnuel;
    }

    public function setTauxAnnuel(?float $tauxAnnuel): self
    {
        $this->tauxAnnuel = $tauxAnnuel;

        return $this;
    }

    public function getMensualite(): float
    {
        return $this->mensualite;
    }

    public function setMensualite(float $mensualite): self
    {
        $this->mensualite = $mensualite;

        return $this;
    }

    public function getAssuranceMensuelle(): ?float
    {
        return $this->assuranceMensuelle;
    }

    public function setAssuranceMensuelle(?float $assuranceMensuelle): self
    {
        $this->assuranceMensuelle = $assuranceMensuelle;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getMontantRembourse(): float
    {
        return max(0.0, $this->montantInitial - $this->capitalRestant);
    }

    public function getProgressPercentage(): float
    {
        if ($this->montantInitial <= 0.0) {
            return 0.0;
        }

        return round(min(100.0, max(0.0, $this->getMontantRembourse() / $this->montantInitial * 100)), 1);
    }

    public function getCoutMensuel(): float
    {
        return $this->mensualite + ($this->assuranceMensuelle ?? 0.0);
    }
}
