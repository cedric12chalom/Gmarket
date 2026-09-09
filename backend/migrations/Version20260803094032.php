<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803094032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute solde à user + table transaction';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD solde NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('CREATE TABLE transaction (
            id INT AUTO_INCREMENT PRIMARY KEY,
            utilisateur_id INT NOT NULL,
            type VARCHAR(10) NOT NULL,
            montant NUMERIC(10,2) NOT NULL,
            motif VARCHAR(50) NOT NULL,
            reference VARCHAR(255) DEFAULT NULL,
            solde_apres NUMERIC(10,2) NOT NULL,
            date_creation DATETIME NOT NULL,
            INDEX IDX_transaction_utilisateur (utilisateur_id),
            CONSTRAINT FK_transaction_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE transaction');
        $this->addSql('ALTER TABLE `user` DROP COLUMN solde');
    }
}