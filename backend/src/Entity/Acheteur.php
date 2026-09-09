<?php

namespace App\Entity;

use App\Repository\AcheteurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AcheteurRepository::class)]
class Acheteur extends User
{
    
}
