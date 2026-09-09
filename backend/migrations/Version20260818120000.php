<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute email_verifie, code_verification et code_verification_expire_le sur user (vérification email)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD email_verifie TINYINT(1) NOT NULL DEFAULT 0, ADD code_verification VARCHAR(6) DEFAULT NULL, ADD code_verification_expire_le DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP email_verifie, DROP code_verification, DROP code_verification_expire_le');
    }
}
