<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803092037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute latitude/longitude à user pour la géolocalisation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD latitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD longitude NUMERIC(10, 7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP COLUMN latitude');
        $this->addSql('ALTER TABLE `user` DROP COLUMN longitude');
    }
}