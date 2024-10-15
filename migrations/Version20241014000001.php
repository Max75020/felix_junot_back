<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241014000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renommer la colonne id_adresse_livraison en adresse_livraison_id dans la table commande';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande RENAME COLUMN id_adresse_livraison TO adresse_livraison_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande RENAME COLUMN adresse_facturation_id TO id_adresse_facturation');
    }
}