<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241105171157 extends AbstractMigration
{
	public function getDescription(): string
	{
		return '';
	}

	public function up(Schema $schema): void
	{
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE adresse (id_adresse INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, type VARCHAR(20) NOT NULL, prenom VARCHAR(50) NOT NULL, nom VARCHAR(50) NOT NULL, rue VARCHAR(255) NOT NULL, batiment VARCHAR(100) DEFAULT NULL, appartement VARCHAR(100) DEFAULT NULL, code_postal VARCHAR(5) NOT NULL, ville VARCHAR(100) NOT NULL, pays VARCHAR(50) NOT NULL, telephone VARCHAR(14) DEFAULT NULL, similaire TINYINT(1) DEFAULT 0 NOT NULL, nom_adresse VARCHAR(255) NOT NULL, INDEX idx_utilisateur_id (utilisateur_id), PRIMARY KEY(id_adresse)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE categorie (id_categorie INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_497DD6346C6E55B5 (nom), INDEX idx_nom (nom), PRIMARY KEY(id_categorie)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE commande (id_commande INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, etat_commande_id INT NOT NULL, adresse_facturation_id INT NOT NULL, adresse_livraison_id INT NOT NULL, transporteur_id INT NOT NULL, panier_id INT NOT NULL, methode_livraison_id INT NOT NULL, date_commande DATETIME NOT NULL, total_produits_commande NUMERIC(10, 2) NOT NULL, poids NUMERIC(10, 2) DEFAULT NULL, frais_livraison NUMERIC(10, 2) NOT NULL, numero_suivi VARCHAR(100) NOT NULL, reference VARCHAR(30) NOT NULL, prix_total_commande NUMERIC(10, 2) NOT NULL, INDEX IDX_6EEAA67D385FD512 (methode_livraison_id), INDEX idx_utilisateur_id (utilisateur_id), INDEX idx_etat_commande_id (etat_commande_id), INDEX idx_date_commande (date_commande), INDEX idx_transporteur_id (transporteur_id), INDEX idx_panier_id (panier_id), INDEX idx_adresse_facturation_id (adresse_facturation_id), INDEX idx_adresse_livraison_id (adresse_livraison_id), PRIMARY KEY(id_commande)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE commande_produit (id_commande_produit INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, prix_total_produit NUMERIC(10, 2) NOT NULL, INDEX idx_commande_id (commande_id), INDEX idx_produit_id (produit_id), PRIMARY KEY(id_commande_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE etat_commande (id_etat_commande INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_F33F0EEDA4D60759 (libelle), INDEX idx_libelle (libelle), PRIMARY KEY(id_etat_commande)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE favoris (id_favoris INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, produit_id INT NOT NULL, INDEX idx_utilisateur_id (utilisateur_id), INDEX idx_produit_id (produit_id), PRIMARY KEY(id_favoris)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE historique_etat_commande (id_historique_etat_commande INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, etat_commande_id INT NOT NULL, date_etat DATETIME NOT NULL, INDEX idx_commande_id (commande_id), INDEX idx_etat_commande_id (etat_commande_id), INDEX idx_date_etat (date_etat), PRIMARY KEY(id_historique_etat_commande)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE image_produit (id_image_produit INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, position INT DEFAULT 0 NOT NULL, cover TINYINT(1) DEFAULT 0 NOT NULL, legend VARCHAR(128) NOT NULL, INDEX idx_produit_id (produit_id), PRIMARY KEY(id_image_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE methode_livraison (id_methode_livraison INT AUTO_INCREMENT NOT NULL, transporteur_id INT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, prix NUMERIC(10, 2) NOT NULL, delai_estime VARCHAR(50) DEFAULT NULL, INDEX IDX_F3464FF297C86FA4 (transporteur_id), PRIMARY KEY(id_methode_livraison)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE panier (id_panier INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, etat VARCHAR(20) NOT NULL, prix_total_panier NUMERIC(10, 2) NOT NULL, INDEX idx_utilisateur_id (utilisateur_id), PRIMARY KEY(id_panier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE panier_produit (id_panier_produit INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, panier_id INT NOT NULL, quantite INT NOT NULL, prix_total_produit NUMERIC(10, 2) NOT NULL, INDEX idx_panier_id (panier_id), INDEX idx_produit_id (produit_id), PRIMARY KEY(id_panier_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE produit (id_produit INT AUTO_INCREMENT NOT NULL, tva_id INT NOT NULL, reference VARCHAR(20) NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, prix_ht NUMERIC(10, 2) NOT NULL, prix_ttc NUMERIC(10, 2) NOT NULL, stock INT NOT NULL, INDEX idx_nom (nom), INDEX idx_prix (prix_ttc), INDEX idx_tva (tva_id), UNIQUE INDEX uq_reference (reference), PRIMARY KEY(id_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE produit_categorie (produit_id INT NOT NULL, categorie_id INT NOT NULL, INDEX IDX_CDEA88D8F347EFB (produit_id), INDEX IDX_CDEA88D8BCF5E72D (categorie_id), PRIMARY KEY(produit_id, categorie_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE transporteur (id_transporteur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id_transporteur)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE tva (id_tva INT AUTO_INCREMENT NOT NULL, taux NUMERIC(5, 2) NOT NULL, UNIQUE INDEX unique_taux (taux), PRIMARY KEY(id_tva)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE utilisateur (id_utilisateur INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(50) NOT NULL, nom VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, telephone VARCHAR(14) DEFAULT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, token_reinitialisation VARCHAR(255) DEFAULT NULL, email_valide TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY(id_utilisateur)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE adresse ADD CONSTRAINT FK_C35F0816FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DEF9E8683 FOREIGN KEY (etat_commande_id) REFERENCES etat_commande (id_etat_commande)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D5BBD1224 FOREIGN KEY (adresse_facturation_id) REFERENCES adresse (id_adresse)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DBE2F0A35 FOREIGN KEY (adresse_livraison_id) REFERENCES adresse (id_adresse)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D97C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteur (id_transporteur)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id_panier)');
		$this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D385FD512 FOREIGN KEY (methode_livraison_id) REFERENCES methode_livraison (id_methode_livraison)');
		$this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E8782EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id_commande)');
		$this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E87F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
		$this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C432FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
		$this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C432F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
		$this->addSql('ALTER TABLE historique_etat_commande ADD CONSTRAINT FK_2720366582EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id_commande)');
		$this->addSql('ALTER TABLE historique_etat_commande ADD CONSTRAINT FK_27203665EF9E8683 FOREIGN KEY (etat_commande_id) REFERENCES etat_commande (id_etat_commande)');
		$this->addSql('ALTER TABLE image_produit ADD CONSTRAINT FK_BCB5BBFBF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
		$this->addSql('ALTER TABLE methode_livraison ADD CONSTRAINT FK_F3464FF297C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteur (id_transporteur)');
		$this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
		$this->addSql('ALTER TABLE panier_produit ADD CONSTRAINT FK_D31F28A6F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
		$this->addSql('ALTER TABLE panier_produit ADD CONSTRAINT FK_D31F28A6F77D927C FOREIGN KEY (panier_id) REFERENCES panier (id_panier)');
		$this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC274D79775F FOREIGN KEY (tva_id) REFERENCES tva (id_tva)');
		$this->addSql('ALTER TABLE produit_categorie ADD CONSTRAINT FK_CDEA88D8F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
		$this->addSql('ALTER TABLE produit_categorie ADD CONSTRAINT FK_CDEA88D8BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id_categorie)');
		// Ajouter la colonne JSON 'roles_generated' et créer une colonne générée
		$this->addSql('ALTER TABLE utilisateur ADD roles_generated VARCHAR(255) AS (JSON_UNQUOTE(JSON_EXTRACT(roles, "$[0]"))) STORED');
		// Indexer la colonne générée
		$this->addSql('CREATE INDEX idx_roles ON utilisateur (roles_generated)');
	}

	public function down(Schema $schema): void
	{
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE adresse DROP FOREIGN KEY FK_C35F0816FB88E14F');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DEF9E8683');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D5BBD1224');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DBE2F0A35');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D97C86FA4');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DF77D927C');
		$this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D385FD512');
		$this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E8782EA2E54');
		$this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E87F347EFB');
		$this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C432FB88E14F');
		$this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C432F347EFB');
		$this->addSql('ALTER TABLE historique_etat_commande DROP FOREIGN KEY FK_2720366582EA2E54');
		$this->addSql('ALTER TABLE historique_etat_commande DROP FOREIGN KEY FK_27203665EF9E8683');
		$this->addSql('ALTER TABLE image_produit DROP FOREIGN KEY FK_BCB5BBFBF347EFB');
		$this->addSql('ALTER TABLE methode_livraison DROP FOREIGN KEY FK_F3464FF297C86FA4');
		$this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2FB88E14F');
		$this->addSql('ALTER TABLE panier_produit DROP FOREIGN KEY FK_D31F28A6F347EFB');
		$this->addSql('ALTER TABLE panier_produit DROP FOREIGN KEY FK_D31F28A6F77D927C');
		$this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC274D79775F');
		$this->addSql('ALTER TABLE produit_categorie DROP FOREIGN KEY FK_CDEA88D8F347EFB');
		$this->addSql('ALTER TABLE produit_categorie DROP FOREIGN KEY FK_CDEA88D8BCF5E72D');
		$this->addSql('DROP TABLE adresse');
		$this->addSql('DROP TABLE categorie');
		$this->addSql('DROP TABLE commande');
		$this->addSql('DROP TABLE commande_produit');
		$this->addSql('DROP TABLE etat_commande');
		$this->addSql('DROP TABLE favoris');
		$this->addSql('DROP TABLE historique_etat_commande');
		$this->addSql('DROP TABLE image_produit');
		$this->addSql('DROP TABLE methode_livraison');
		$this->addSql('DROP TABLE panier');
		$this->addSql('DROP TABLE panier_produit');
		$this->addSql('DROP TABLE produit');
		$this->addSql('DROP TABLE produit_categorie');
		$this->addSql('DROP TABLE refresh_tokens');
		$this->addSql('DROP TABLE transporteur');
		$this->addSql('DROP TABLE tva');
		$this->addSql('DROP TABLE utilisateur');
		$this->addSql('DROP INDEX idx_nom ON categorie');
		$this->addSql('DROP INDEX idx_roles ON utilisateur');
		$this->addSql('ALTER TABLE utilisateur ADD role VARCHAR(20) NOT NULL, DROP roles');
	}
}
