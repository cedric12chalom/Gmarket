<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260712150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial TerraLink schema matching MLD entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categorie (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            type_transport VARCHAR(20) NOT NULL,
            commission_fixe NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            commission_pourcent NUMERIC(5,2) NOT NULL DEFAULT 0.00,
            approuve TINYINT(1) NOT NULL DEFAULT 0
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE `user` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(180) NOT NULL UNIQUE,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom VARCHAR(255) NOT NULL,
            prenom VARCHAR(255) NOT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            role VARCHAR(20) NOT NULL,
            statut VARCHAR(20) NOT NULL,
            localisation VARCHAR(255) DEFAULT NULL,
            adresse_livraison VARCHAR(255) DEFAULT NULL,
            type_transport VARCHAR(20) DEFAULT NULL,
            date_inscription DATETIME DEFAULT NULL,
            description_exploitation VARCHAR(255) DEFAULT NULL,
            disponible TINYINT(1) DEFAULT 1
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE produit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            unite VARCHAR(50) NOT NULL,
            prix_min NUMERIC(10,2) NOT NULL,
            prix_max NUMERIC(10,2) NOT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            categorie_id INT NOT NULL,
            INDEX IDX_produit_categorie (categorie_id),
            CONSTRAINT FK_produit_categorie FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE lot (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quantite_disponible NUMERIC(10,2) NOT NULL,
            quantite_reservee NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            prix_producteur NUMERIC(10,2) NOT NULL,
            date_recolte DATE NOT NULL,
            duree_conservation INT NOT NULL,
            variete VARCHAR(255) DEFAULT NULL,
            lieu_production VARCHAR(255) DEFAULT NULL,
            qr_code VARCHAR(255) DEFAULT NULL UNIQUE,
            statut VARCHAR(20) NOT NULL,
            producteur_id INT NOT NULL,
            produit_id INT NOT NULL,
            INDEX IDX_lot_producteur (producteur_id),
            INDEX IDX_lot_produit (produit_id),
            CONSTRAINT FK_lot_producteur FOREIGN KEY (producteur_id) REFERENCES `user` (id),
            CONSTRAINT FK_lot_produit FOREIGN KEY (produit_id) REFERENCES produit (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE commande (
            id INT AUTO_INCREMENT PRIMARY KEY,
            acheteur_id INT NOT NULL,
            statut VARCHAR(20) NOT NULL,
            mode_recuperation VARCHAR(20) NOT NULL,
            date_commande DATETIME NOT NULL,
            montant_produits NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            commission NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            frais_livraison NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            montant_total NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            INDEX IDX_commande_acheteur (acheteur_id),
            CONSTRAINT FK_commande_acheteur FOREIGN KEY (acheteur_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE ligne_commande (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL,
            lot_id INT NOT NULL,
            quantite NUMERIC(10,2) NOT NULL,
            prix_unitaire NUMERIC(10,2) NOT NULL,
            sous_total NUMERIC(10,2) NOT NULL,
            INDEX IDX_ligne_commande_commande (commande_id),
            INDEX IDX_ligne_commande_lot (lot_id),
            CONSTRAINT FK_ligne_commande_commande FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE,
            CONSTRAINT FK_ligne_commande_lot FOREIGN KEY (lot_id) REFERENCES lot (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE paiement (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL,
            montant NUMERIC(10,2) NOT NULL,
            methode VARCHAR(50) NOT NULL,
            statut VARCHAR(20) NOT NULL,
            reference VARCHAR(255) NOT NULL UNIQUE,
            date_paiement DATETIME DEFAULT NULL,
            INDEX IDX_paiement_commande (commande_id),
            CONSTRAINT FK_paiement_commande FOREIGN KEY (commande_id) REFERENCES commande (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE livraison (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL UNIQUE,
            livreur_id INT DEFAULT NULL,
            statut VARCHAR(20) NOT NULL,
            adresse_livraison VARCHAR(255) DEFAULT NULL,
            date_retrait DATETIME DEFAULT NULL,
            date_livraison DATETIME DEFAULT NULL,
            photo_retrait VARCHAR(255) DEFAULT NULL,
            photo_livraison VARCHAR(255) DEFAULT NULL,
            frais NUMERIC(10,2) NOT NULL DEFAULT 0.00,
            INDEX IDX_livraison_commande (commande_id),
            INDEX IDX_livraison_livreur (livreur_id),
            CONSTRAINT FK_livraison_commande FOREIGN KEY (commande_id) REFERENCES commande (id),
            CONSTRAINT FK_livraison_livreur FOREIGN KEY (livreur_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE litige (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL,
            motif VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            statut VARCHAR(20) NOT NULL,
            date_creation DATETIME NOT NULL,
            resolution LONGTEXT DEFAULT NULL,
            INDEX IDX_litige_commande (commande_id),
            CONSTRAINT FK_litige_commande FOREIGN KEY (commande_id) REFERENCES commande (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            utilisateur_id INT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            lu TINYINT(1) NOT NULL DEFAULT 0,
            date_envoi DATETIME NOT NULL,
            INDEX IDX_notification_utilisateur (utilisateur_id),
            CONSTRAINT FK_notification_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES `user` (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE avis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            auteur_id INT NOT NULL,
            cible_id INT NOT NULL,
            note INT NOT NULL,
            commentaire LONGTEXT DEFAULT NULL,
            date_avis DATETIME NOT NULL,
            INDEX IDX_avis_auteur (auteur_id),
            INDEX IDX_avis_cible (cible_id),
            CONSTRAINT FK_avis_auteur FOREIGN KEY (auteur_id) REFERENCES `user` (id),
            CONSTRAINT FK_avis_cible FOREIGN KEY (cible_id) REFERENCES `user` (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE litige');
        $this->addSql('DROP TABLE livraison');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE lot');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }
}
