<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241013225101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande ADD methode_livraison_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D385FD512 FOREIGN KEY (methode_livraison_id) REFERENCES methode_livraison (id_methode_livraison)');
        $this->addSql('CREATE INDEX IDX_6EEAA67D385FD512 ON commande (methode_livraison_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D385FD512');
        $this->addSql('DROP INDEX IDX_6EEAA67D385FD512 ON commande');
        $this->addSql('ALTER TABLE commande DROP methode_livraison_id');
    }
}
