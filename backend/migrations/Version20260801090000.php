<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute campay_reference et numero sur paiement pour l\'intégration Campay';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE paiement ADD campay_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement ADD numero VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE paiement DROP COLUMN campay_reference');
        $this->addSql('ALTER TABLE paiement DROP COLUMN numero');
    }
}
