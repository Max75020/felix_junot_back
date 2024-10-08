<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008105107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_prix ON produit (prix_ttc)');
        $this->addSql('ALTER TABLE tva DROP INDEX idx_taux, ADD UNIQUE INDEX unique_taux (taux)');
        $this->addSql('ALTER TABLE tva CHANGE taux taux DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tva DROP INDEX unique_taux, ADD INDEX idx_taux (taux)');
        $this->addSql('ALTER TABLE tva CHANGE taux taux NUMERIC(4, 2) NOT NULL');
        $this->addSql('DROP INDEX idx_roles ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD roles_generated VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_roles_generated ON utilisateur (roles_generated)');
        $this->addSql('DROP INDEX idx_prix ON produit');
    }
}
