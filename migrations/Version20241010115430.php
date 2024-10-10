<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241010115430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD id_adresse_facturation INT NOT NULL, ADD id_adresse_livraison INT NOT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D9F0F341E FOREIGN KEY (id_adresse_facturation) REFERENCES adresse (id_adresse)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFA3C61DE FOREIGN KEY (id_adresse_livraison) REFERENCES adresse (id_adresse)');
        $this->addSql('CREATE INDEX idx_adresse_facturation_id ON commande (id_adresse_facturation)');
        $this->addSql('CREATE INDEX idx_adresse_livraison_id ON commande (id_adresse_livraison)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D9F0F341E');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFA3C61DE');
        $this->addSql('DROP INDEX idx_adresse_facturation_id ON commande');
        $this->addSql('DROP INDEX idx_adresse_livraison_id ON commande');
        $this->addSql('ALTER TABLE commande DROP id_adresse_facturation, DROP id_adresse_livraison');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_METHODE_LIVRAISON_NOM_TRANSPORTEUR ON methode_livraison (nom, transporteur_id)');
        $this->addSql('DROP INDEX idx_roles ON utilisateur');
    }
}
