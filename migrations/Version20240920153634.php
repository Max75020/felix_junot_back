<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240920153634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_date_commande ON commande (date_commande)');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_6eeaa67dfb88e14f TO idx_utilisateur_id');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_6eeaa67def9e8683 TO idx_etat_commande_id');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX idx_df1e9e8782ea2e54 TO idx_commande_id');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX idx_df1e9e87f347efb TO idx_produit_id');
        $this->addSql('CREATE INDEX idx_libelle ON etat_commande (libelle)');
        $this->addSql('ALTER TABLE favoris RENAME INDEX idx_8933c432fb88e14f TO idx_utilisateur_id');
        $this->addSql('ALTER TABLE favoris RENAME INDEX idx_8933c432f347efb TO idx_produit_id');
        $this->addSql('CREATE INDEX idx_date_etat ON historique_etat_commande (date_etat)');
        $this->addSql('ALTER TABLE historique_etat_commande RENAME INDEX idx_2720366582ea2e54 TO idx_commande_id');
        $this->addSql('ALTER TABLE historique_etat_commande RENAME INDEX idx_27203665ef9e8683 TO idx_etat_commande_id');
        $this->addSql('ALTER TABLE image_produit RENAME INDEX idx_bcb5bbfbf347efb TO idx_produit_id');
        $this->addSql('ALTER TABLE panier RENAME INDEX idx_24cc0df2fb88e14f TO idx_utilisateur_id');
        $this->addSql('ALTER TABLE panier_produit RENAME INDEX idx_d31f28a6f77d927c TO idx_panier_id');
        $this->addSql('ALTER TABLE panier_produit RENAME INDEX idx_d31f28a6f347efb TO idx_produit_id');
        $this->addSql('CREATE INDEX idx_nom ON produit (nom)');
        $this->addSql('CREATE INDEX idx_prix ON produit (prix)');
        $this->addSql('CREATE UNIQUE INDEX uq_reference ON produit (reference)');
        $this->addSql('ALTER TABLE produit RENAME INDEX idx_29a5ec27bcf5e72d TO idx_categorie_id');
        $this->addSql('CREATE INDEX idx_taux ON tva (taux)');
        $this->addSql('CREATE INDEX idx_email ON utilisateur (email)');
        $this->addSql('CREATE INDEX idx_role ON utilisateur (role)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_taux ON tva');
        $this->addSql('DROP INDEX idx_date_etat ON historique_etat_commande');
        $this->addSql('ALTER TABLE historique_etat_commande RENAME INDEX idx_etat_commande_id TO IDX_27203665EF9E8683');
        $this->addSql('ALTER TABLE historique_etat_commande RENAME INDEX idx_commande_id TO IDX_2720366582EA2E54');
        $this->addSql('ALTER TABLE image_produit RENAME INDEX idx_produit_id TO IDX_BCB5BBFBF347EFB');
        $this->addSql('ALTER TABLE favoris RENAME INDEX idx_produit_id TO IDX_8933C432F347EFB');
        $this->addSql('ALTER TABLE favoris RENAME INDEX idx_utilisateur_id TO IDX_8933C432FB88E14F');
        $this->addSql('DROP INDEX idx_libelle ON etat_commande');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX idx_produit_id TO IDX_DF1E9E87F347EFB');
        $this->addSql('ALTER TABLE commande_produit RENAME INDEX idx_commande_id TO IDX_DF1E9E8782EA2E54');
        $this->addSql('ALTER TABLE panier RENAME INDEX idx_utilisateur_id TO IDX_24CC0DF2FB88E14F');
        $this->addSql('DROP INDEX idx_date_commande ON commande');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_etat_commande_id TO IDX_6EEAA67DEF9E8683');
        $this->addSql('ALTER TABLE commande RENAME INDEX idx_utilisateur_id TO IDX_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE panier_produit RENAME INDEX idx_panier_id TO IDX_D31F28A6F77D927C');
        $this->addSql('ALTER TABLE panier_produit RENAME INDEX idx_produit_id TO IDX_D31F28A6F347EFB');
        $this->addSql('DROP INDEX idx_email ON utilisateur');
        $this->addSql('DROP INDEX idx_role ON utilisateur');
        $this->addSql('DROP INDEX idx_nom ON produit');
        $this->addSql('DROP INDEX idx_prix ON produit');
        $this->addSql('DROP INDEX uq_reference ON produit');
        $this->addSql('ALTER TABLE produit RENAME INDEX idx_categorie_id TO IDX_29A5EC27BCF5E72D');
    }
}
