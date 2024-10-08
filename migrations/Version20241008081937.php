<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008081937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_prix ON produit');
        $this->addSql('ALTER TABLE produit ADD prix_ttc NUMERIC(10, 2) NOT NULL, CHANGE prix prix_ht NUMERIC(10, 2) NOT NULL');
        $this->addSql('CREATE INDEX idx_prix ON produit (prix_ttc)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_roles ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur ADD roles_generated VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_roles ON utilisateur (roles_generated)');
        $this->addSql('DROP INDEX idx_prix ON produit');
        $this->addSql('ALTER TABLE produit ADD prix NUMERIC(10, 2) NOT NULL, DROP prix_ht, DROP prix_ttc');
        $this->addSql('CREATE INDEX idx_prix ON produit (prix)');
    }
}
