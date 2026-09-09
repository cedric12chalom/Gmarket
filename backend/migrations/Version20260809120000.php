<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les varietes de produits et relie les lots a une variete optionnelle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE variete (
            id INT AUTO_INCREMENT PRIMARY KEY,
            produit_id INT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            INDEX IDX_variete_produit (produit_id),
            UNIQUE INDEX uniq_variete_produit_nom (produit_id, nom),
            CONSTRAINT FK_variete_produit FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE lot ADD variete_id INT DEFAULT NULL');

        $this->addSql("INSERT IGNORE INTO variete (produit_id, nom)
            SELECT DISTINCT produit_id, variete
            FROM lot
            WHERE variete IS NOT NULL AND variete <> ''");

        $this->addSql('UPDATE lot l
            INNER JOIN variete v ON v.produit_id = l.produit_id AND v.nom = l.variete
            SET l.variete_id = v.id
            WHERE l.variete IS NOT NULL AND l.variete <> ""');

        $this->addSql('ALTER TABLE lot DROP variete');
        $this->addSql('CREATE INDEX IDX_lot_variete ON lot (variete_id)');
        $this->addSql('ALTER TABLE lot ADD CONSTRAINT FK_lot_variete FOREIGN KEY (variete_id) REFERENCES variete (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lot ADD variete VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE lot l LEFT JOIN variete v ON v.id = l.variete_id SET l.variete = v.nom');
        $this->addSql('ALTER TABLE lot DROP FOREIGN KEY FK_lot_variete');
        $this->addSql('DROP INDEX IDX_lot_variete ON lot');
        $this->addSql('ALTER TABLE lot DROP variete_id');
        $this->addSql('DROP TABLE variete');
    }
}
