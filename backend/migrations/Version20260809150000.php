<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute chat_actif et telephone_service sur le livreur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD chat_actif TINYINT(1) DEFAULT 1 NOT NULL, ADD telephone_service VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP chat_actif, DROP telephone_service');
    }
}
