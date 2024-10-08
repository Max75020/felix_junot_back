<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241008100108 extends AbstractMigration
{
	public function getDescription(): string
	{
		return '';
	}

	public function up(Schema $schema): void
	{
		// Crée la table transporteurs si elle n'existe pas
		$this->addSql('CREATE TABLE IF NOT EXISTS transporteurs (id_transporteur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id_transporteur)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');

		// Insère un transporteur par défaut
		$this->addSql("INSERT INTO transporteurs (nom) VALUES ('Transporteur par défaut')");

		// Récupère l'ID du transporteur par défaut
		$this->addSql("SET @default_transporteur_id = LAST_INSERT_ID()");

		// Vérifie manuellement si la colonne transporteur_id existe avant de l'ajouter
		$schemaManager = $this->connection->createSchemaManager();
		$columns = $schemaManager->listTableColumns('commande');

		if (!array_key_exists('transporteur_id', $columns)) {
			// Ajoute la colonne transporteur_id avec une valeur par défaut NULL
			$this->addSql('ALTER TABLE commande ADD transporteur_id INT DEFAULT NULL');
		}

		// Met à jour les enregistrements existants de la table commande pour utiliser le transporteur par défaut
		$this->addSql('UPDATE commande SET transporteur_id = @default_transporteur_id WHERE transporteur_id IS NULL OR transporteur_id = 0');

		// Modifie la colonne pour la rendre non nullable après avoir mis à jour les enregistrements existants
		$this->addSql('ALTER TABLE commande MODIFY transporteur_id INT NOT NULL');

		// Ajoute la contrainte de clé étrangère à la table commande
		//$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D97C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteurs (id_transporteur)');

		// Crée un index sur la colonne transporteur_id
		$this->addSql('CREATE INDEX idx_transporteurs_id ON commande (transporteur_id)');

		// Vérifie si l'index existe avant de tenter de le renommer dans la table produit
		$indexes = $schemaManager->listTableIndexes('produit');
		if (isset($indexes['idx_29a5ec274d79775f'])) {
			$this->addSql('ALTER TABLE produit RENAME INDEX idx_29a5ec274d79775f TO idx_tva');
		}

		// Ne crée PAS d'index direct sur la colonne JSON roles
		// Ajouter une colonne générée pour stocker le premier rôle de l'utilisateur, si elle n'existe pas déjà
		//$this->addSql('ALTER TABLE utilisateur ADD COLUMN roles_generated VARCHAR(255) AS (JSON_UNQUOTE(JSON_EXTRACT(roles, "$[0]"))) STORED');

		// Indexer la colonne générée roles_generated au lieu de la colonne JSON directe
		//$this->addSql('CREATE INDEX idx_roles_generated ON utilisateur (roles_generated)');
	}



	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D97C86FA4');
		$this->addSql('DROP TABLE transporteurs');
		$this->addSql('DROP INDEX idx_transporteurs_id ON commande');
		$this->addSql('ALTER TABLE commande ADD transporteur VARCHAR(100) NOT NULL, DROP transporteur_id');
		$this->addSql('DROP INDEX idx_roles ON utilisateur');
		$this->addSql('DROP INDEX idx_prix ON produit');
		$this->addSql('ALTER TABLE produit RENAME INDEX idx_tva TO IDX_29A5EC274D79775F');
	}
}
