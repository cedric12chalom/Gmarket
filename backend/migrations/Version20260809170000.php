<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute ville/quartier (User) et les champs onboarding livreur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD ville VARCHAR(255) DEFAULT NULL, ADD quartier VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD cni_numero VARCHAR(50) DEFAULT NULL, ADD cni_photo VARCHAR(255) DEFAULT NULL, ADD photo_profil VARCHAR(255) DEFAULT NULL, ADD moyen_deplacement VARCHAR(20) DEFAULT NULL, ADD vehicule_plaque VARCHAR(50) DEFAULT NULL, ADD vehicule_marque_modele VARCHAR(255) DEFAULT NULL, ADD mobile_money_numero VARCHAR(50) DEFAULT NULL, ADD contact_urgence_nom VARCHAR(255) DEFAULT NULL, ADD contact_urgence_telephone VARCHAR(50) DEFAULT NULL, ADD conditions_livraison_accepte_le DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP ville, DROP quartier');
        $this->addSql('ALTER TABLE `user` DROP cni_numero, DROP cni_photo, DROP photo_profil, DROP moyen_deplacement, DROP vehicule_plaque, DROP vehicule_marque_modele, DROP mobile_money_numero, DROP contact_urgence_nom, DROP contact_urgence_telephone, DROP conditions_livraison_accepte_le');
    }
}
