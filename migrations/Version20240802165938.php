<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240802165938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adresse (id_adresse INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, type VARCHAR(20) NOT NULL, prenom VARCHAR(50) NOT NULL, nom VARCHAR(50) NOT NULL, rue VARCHAR(255) NOT NULL, batiment VARCHAR(100) DEFAULT NULL, appartement VARCHAR(100) DEFAULT NULL, code_postal VARCHAR(20) NOT NULL, ville VARCHAR(100) NOT NULL, pays VARCHAR(50) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, similaire TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_C35F0816FB88E14F (utilisateur_id), PRIMARY KEY(id_adresse)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categorie (id_categorie INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id_categorie)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande (id_commande INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, etat_commande_id INT NOT NULL, date_commande DATE NOT NULL, total NUMERIC(10, 2) NOT NULL, transporteur VARCHAR(100) NOT NULL, poids NUMERIC(10, 2) NOT NULL, frais_livraison NUMERIC(10, 2) NOT NULL, numero_suivi VARCHAR(100) NOT NULL, INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), INDEX IDX_6EEAA67DEF9E8683 (etat_commande_id), PRIMARY KEY(id_commande)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_produit (id_commande_produit INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, quantite INT NOT NULL, INDEX IDX_DF1E9E8782EA2E54 (commande_id), INDEX IDX_DF1E9E87F347EFB (produit_id), PRIMARY KEY(id_commande_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE etat_commande (id_etat_commande INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(50) NOT NULL, PRIMARY KEY(id_etat_commande)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE favoris (id_favoris INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_8933C432FB88E14F (utilisateur_id), INDEX IDX_8933C432F347EFB (produit_id), PRIMARY KEY(id_favoris)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historique_etat_commande (id_historique_etat_commande INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, etat_commande_id INT NOT NULL, date_etat DATE NOT NULL, INDEX IDX_2720366582EA2E54 (commande_id), INDEX IDX_27203665EF9E8683 (etat_commande_id), PRIMARY KEY(id_historique_etat_commande)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image_produit (id_image_produit INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, position INT DEFAULT 0 NOT NULL, cover TINYINT(1) DEFAULT 0 NOT NULL, legend VARCHAR(128) NOT NULL, INDEX IDX_BCB5BBFBF347EFB (produit_id), PRIMARY KEY(id_image_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier (id_panier INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_24CC0DF2FB88E14F (utilisateur_id), PRIMARY KEY(id_panier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE panier_produit (id_panier_produit INT AUTO_INCREMENT NOT NULL, produit_id INT NOT NULL, panier_id INT NOT NULL, quantite INT NOT NULL, INDEX IDX_D31F28A6F347EFB (produit_id), INDEX IDX_D31F28A6F77D927C (panier_id), PRIMARY KEY(id_panier_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE produit (id_produit INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, tva_id INT NOT NULL, reference VARCHAR(32) NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, prix NUMERIC(10, 2) NOT NULL, INDEX IDX_29A5EC27BCF5E72D (categorie_id), INDEX IDX_29A5EC274D79775F (tva_id), PRIMARY KEY(id_produit)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tva (id_tva INT AUTO_INCREMENT NOT NULL, taux NUMERIC(4, 2) NOT NULL, PRIMARY KEY(id_tva)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id_utilisateur INT AUTO_INCREMENT NOT NULL, prenom VARCHAR(50) NOT NULL, nom VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, token_reinitialisation VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email), PRIMARY KEY(id_utilisateur)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE adresse ADD CONSTRAINT FK_C35F0816FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DEF9E8683 FOREIGN KEY (etat_commande_id) REFERENCES etat_commande (id_etat_commande)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E8782EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id_commande)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E87F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C432FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('ALTER TABLE favoris ADD CONSTRAINT FK_8933C432F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
        $this->addSql('ALTER TABLE historique_etat_commande ADD CONSTRAINT FK_2720366582EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id_commande)');
        $this->addSql('ALTER TABLE historique_etat_commande ADD CONSTRAINT FK_27203665EF9E8683 FOREIGN KEY (etat_commande_id) REFERENCES etat_commande (id_etat_commande)');
        $this->addSql('ALTER TABLE image_produit ADD CONSTRAINT FK_BCB5BBFBF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id_utilisateur)');
        $this->addSql('ALTER TABLE panier_produit ADD CONSTRAINT FK_D31F28A6F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
        $this->addSql('ALTER TABLE panier_produit ADD CONSTRAINT FK_D31F28A6F77D927C FOREIGN KEY (panier_id) REFERENCES panier (id_panier)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id_categorie)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC274D79775F FOREIGN KEY (tva_id) REFERENCES tva (id_tva)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adresse DROP FOREIGN KEY FK_C35F0816FB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DEF9E8683');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E8782EA2E54');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E87F347EFB');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C432FB88E14F');
        $this->addSql('ALTER TABLE favoris DROP FOREIGN KEY FK_8933C432F347EFB');
        $this->addSql('ALTER TABLE historique_etat_commande DROP FOREIGN KEY FK_2720366582EA2E54');
        $this->addSql('ALTER TABLE historique_etat_commande DROP FOREIGN KEY FK_27203665EF9E8683');
        $this->addSql('ALTER TABLE image_produit DROP FOREIGN KEY FK_BCB5BBFBF347EFB');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2FB88E14F');
        $this->addSql('ALTER TABLE panier_produit DROP FOREIGN KEY FK_D31F28A6F347EFB');
        $this->addSql('ALTER TABLE panier_produit DROP FOREIGN KEY FK_D31F28A6F77D927C');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC274D79775F');
        $this->addSql('DROP TABLE adresse');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_produit');
        $this->addSql('DROP TABLE etat_commande');
        $this->addSql('DROP TABLE favoris');
        $this->addSql('DROP TABLE historique_etat_commande');
        $this->addSql('DROP TABLE image_produit');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE panier_produit');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE tva');
        $this->addSql('DROP TABLE utilisateur');
    }
}
