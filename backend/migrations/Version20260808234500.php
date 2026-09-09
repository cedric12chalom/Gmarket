<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808234500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute cgu_accepte_le sur user et latitude/longitude sur lot';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD cgu_accepte_le DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE lot ADD latitude DECIMAL(10, 7) DEFAULT NULL, ADD longitude DECIMAL(10, 7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP cgu_accepte_le');
        $this->addSql('ALTER TABLE lot DROP latitude, DROP longitude');
    }
}
