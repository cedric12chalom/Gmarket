<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime le mode de recuperation "retrait chez le producteur" : bascule les commandes existantes vers la livraison avec frais de livraison.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE commande SET mode_recuperation = 'livraison', frais_livraison = '2.50' WHERE mode_recuperation = 'retrait'");
    }

    public function down(Schema $schema): void
    {
        // Irréversible : impossible de distinguer les commandes historiquement « retrait ».
    }
}
