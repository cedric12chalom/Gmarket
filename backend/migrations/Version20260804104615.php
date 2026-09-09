<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804104615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la relation Litige -> Administrateur (traite)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE litige ADD admin_traiteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE litige ADD CONSTRAINT FK_litige_admin FOREIGN KEY (admin_traiteur_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_litige_admin ON litige (admin_traiteur_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE litige DROP FOREIGN KEY FK_litige_admin');
        $this->addSql('DROP INDEX IDX_litige_admin ON litige');
        $this->addSql('ALTER TABLE litige DROP COLUMN admin_traiteur_id');
    }
}
