<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table demande_produit pour les demandes d\'ajout de produits/catégories par les producteurs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE demande_produit (id INT AUTO_INCREMENT NOT NULL, producteur_id INT NOT NULL, nom_produit VARCHAR(255) NOT NULL, categorie_suggeree VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, unite VARCHAR(50) DEFAULT NULL, statut VARCHAR(20) NOT NULL, motif_refus LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, date_traitement DATETIME DEFAULT NULL, INDEX IDX_demande_produit_producteur (producteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_produit ADD CONSTRAINT FK_demande_produit_producteur FOREIGN KEY (producteur_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE demande_produit');
    }
}
