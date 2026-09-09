<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Administrateur extends User
{
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $nom = null;

    public function __construct()
    {
        parent::__construct();
        $this->roles = ['ROLE_ADMIN'];
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }
}