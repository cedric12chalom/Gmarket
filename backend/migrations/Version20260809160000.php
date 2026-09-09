<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute date_reception_confirmee sur la livraison (confirmation acheteur)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE livraison ADD date_reception_confirmee DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE livraison DROP date_reception_confirmee');
    }
}
