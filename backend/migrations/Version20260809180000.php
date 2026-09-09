<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le statut de verification du dossier livreur (statut_verification, motif_refus, verifie_le, valide_le) sur la table user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD statut_verification VARCHAR(30) DEFAULT \'en_attente\' NOT NULL, ADD motif_refus VARCHAR(255) DEFAULT NULL, ADD verifie_le DATETIME DEFAULT NULL, ADD valide_le DATETIME DEFAULT NULL');
        $this->addSql('UPDATE `user` SET statut_verification = \'valide\' WHERE role = \'livreur\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP statut_verification, DROP motif_refus, DROP verifie_le, DROP valide_le');
    }
}
