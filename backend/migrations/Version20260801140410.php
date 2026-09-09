<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801140410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table message pour le chat livreur/acheteur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE message (
            id INT AUTO_INCREMENT PRIMARY KEY,
            livraison_id INT NOT NULL,
            expediteur_id INT NOT NULL,
            contenu LONGTEXT NOT NULL,
            date_envoi DATETIME NOT NULL,
            lu TINYINT(1) NOT NULL DEFAULT 0,
            INDEX IDX_message_livraison (livraison_id),
            INDEX IDX_message_expediteur (expediteur_id),
            CONSTRAINT FK_message_livraison FOREIGN KEY (livraison_id) REFERENCES livraison (id) ON DELETE CASCADE,
            CONSTRAINT FK_message_expediteur FOREIGN KEY (expediteur_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE message');
    }
}
