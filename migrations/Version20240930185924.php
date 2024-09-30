<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240930185924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('DROP INDEX idx_role ON utilisateur');
		// Ajouter la colonne JSON 'roles' et créer une colonne générée
		$this->addSql('ALTER TABLE utilisateur ADD roles JSON NOT NULL');
		$this->addSql('ALTER TABLE utilisateur ADD roles_generated VARCHAR(255) AS (JSON_UNQUOTE(JSON_EXTRACT(roles, "$[0]"))) STORED');
		// Indexer la colonne générée
		$this->addSql('CREATE INDEX idx_roles ON utilisateur (roles_generated)');
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
