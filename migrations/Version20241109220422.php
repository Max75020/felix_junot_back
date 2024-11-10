<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241109220422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande CHANGE numero_suivi numero_suivi VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE image_produit ADD chemin VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX idx_roles ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP roles_generated');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image_produit DROP chemin');
        $this->addSql('ALTER TABLE commande CHANGE numero_suivi numero_suivi VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD roles_generated VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_roles ON utilisateur (roles_generated)');
    }
}
