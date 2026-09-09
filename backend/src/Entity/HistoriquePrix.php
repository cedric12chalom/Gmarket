<?php

namespace App\Entity;

use App\Repository\HistoriquePrixRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriquePrixRepository::class)]
class HistoriquePrix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixMoyen = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateReleve = null;

    public function getId(): ?int { return $this->id; }
    public function getProduit(): ?Produit { return $this->produit; }
    public function setProduit(?Produit $produit): self { $this->produit = $produit; return $this; }
    public function getPrixMoyen(): ?string { return $this->prixMoyen; }
    public function setPrixMoyen(string $prixMoyen): self { $this->prixMoyen = $prixMoyen; return $this; }
    public function getDateReleve(): ?\DateTimeInterface { return $this->dateReleve; }
    public function setDateReleve(?\DateTimeInterface $dateReleve): self { $this->dateReleve = $dateReleve; return $this; }
}