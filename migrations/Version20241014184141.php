<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241014184141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D97C86FA4');
        $this->addSql('ALTER TABLE methode_livraison DROP FOREIGN KEY FK_F3464FF297C86FA4');
        $this->addSql('CREATE TABLE transporteur (id_transporteur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY(id_transporteur)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE transporteurs');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D97C86FA4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D97C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteur (id_transporteur)');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_transporteurs_id TO idx_transporteur_id');
        $this->addSql('ALTER TABLE methode_livraison DROP FOREIGN KEY FK_F3464FF297C86FA4');
        $this->addSql('ALTER TABLE methode_livraison ADD CONSTRAINT FK_F3464FF297C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteur (id_transporteur)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D97C86FA4');
        $this->addSql('ALTER TABLE methode_livraison DROP FOREIGN KEY FK_F3464FF297C86FA4');
        $this->addSql('CREATE TABLE transporteurs (id_transporteur INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb3 NOT NULL COLLATE `utf8mb3_unicode_ci`, PRIMARY KEY(id_transporteur)) DEFAULT CHARACTER SET utf8mb3 COLLATE `utf8mb3_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE transporteur');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D97C86FA4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D97C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteurs (id_transporteur) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_transporteur_id TO idx_transporteurs_id');
        $this->addSql('ALTER TABLE methode_livraison DROP FOREIGN KEY FK_F3464FF297C86FA4');
        $this->addSql('ALTER TABLE methode_livraison ADD CONSTRAINT FK_F3464FF297C86FA4 FOREIGN KEY (transporteur_id) REFERENCES transporteurs (id_transporteur) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
