<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805123200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée la table geo_track pour le suivi GPS temps réel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE geo_track (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            type VARCHAR(20) NOT NULL,
            reference_id INT NOT NULL,
            updated_at DATETIME NOT NULL,
            emoji VARCHAR(10) DEFAULT NULL,
            route_geojson LONGTEXT DEFAULT NULL,
            estimated_distance DECIMAL(10,2) DEFAULT NULL,
            estimated_duration DECIMAL(10,2) DEFAULT NULL,
            INDEX idx_geo_lat_lng (latitude, longitude),
            INDEX idx_geo_updated_at (updated_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE geo_track');
    }
}