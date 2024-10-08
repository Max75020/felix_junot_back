<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008110945 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tva CHANGE taux taux NUMERIC(5, 2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tva CHANGE taux taux DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX idx_roles ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD roles_generated VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_roles_generated ON utilisateur (roles_generated)');
    }
}
