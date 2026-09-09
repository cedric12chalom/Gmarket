<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute table historique_prix pour le suivi des prix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE historique_prix (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produit_id INT NOT NULL,
            prix_moyen NUMERIC(10,2) NOT NULL,
            date_releve DATE NOT NULL,
            INDEX IDX_historique_prix_produit (produit_id),
            CONSTRAINT FK_historique_prix_produit FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE historique_prix');
    }
}