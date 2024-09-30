<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240930194455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_nom ON categorie (nom)');
        $this->addSql('DROP INDEX idx_role ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD roles JSON NOT NULL, DROP role');
        $this->addSql('CREATE INDEX idx_roles ON utilisateur (roles)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_nom ON categorie');
        $this->addSql('DROP INDEX idx_roles ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD role VARCHAR(20) NOT NULL, DROP roles');
        $this->addSql('CREATE INDEX idx_role ON utilisateur (role)');
    }
}
