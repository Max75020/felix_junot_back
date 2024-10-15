# Réinitialisation de la base de données
# Suppression des données de la table adresse
TRUNCATE TABLE `felix_test`.`adresse`;
ALTER TABLE `adresse` auto_increment = 1;
# Suppression des données de la table categorie
TRUNCATE TABLE `felix_test`.`categorie`;
ALTER TABLE `categorie` auto_increment = 1;
# Suppression des données de la table commande
TRUNCATE TABLE `felix_test`.`commande`;
ALTER TABLE `commande` auto_increment = 1;
# Suppression des données de la table commande_produit
TRUNCATE TABLE `felix_test`.`commande_produit`;
ALTER TABLE `commande_produit` auto_increment = 1;
# Suppression des données de la table etat_commande
TRUNCATE TABLE `felix_test`.`etat_commande`;
ALTER TABLE `etat_commande` auto_increment = 1;
# Suppression des données de la table favoris
TRUNCATE TABLE `felix_test`.`favoris`;
ALTER TABLE `favoris` auto_increment = 1;
# Suppression des données de la table historique_etat_commande
TRUNCATE TABLE `felix_test`.`historique_etat_commande`;
ALTER TABLE `historique_etat_commande` auto_increment = 1;
# Suppression des données de la table image_produit
TRUNCATE TABLE `felix_test`.`image_produit`;
ALTER TABLE `image_produit` auto_increment = 1;
# Suppression des données de la table panier
TRUNCATE TABLE `felix_test`.`panier`;
ALTER TABLE `panier` auto_increment = 1;
# Suppression des données de la table panier_produit
TRUNCATE TABLE `felix_test`.`panier_produit`;
ALTER TABLE `panier_produit` auto_increment = 1;
# Suppression des données de la table produit
TRUNCATE TABLE `felix_test`.`produit`;
ALTER TABLE `produit` auto_increment = 1;
# Suppression des données de la table produit_categorie
TRUNCATE TABLE `felix_test`.`produit_categorie`;
ALTER TABLE `produit_categorie` auto_increment = 1;
# Suppression des données de la table tva
TRUNCATE TABLE `felix_test`.`tva`;
ALTER TABLE `tva` auto_increment = 1;
# Suppression des données de la table utilisateur
TRUNCATE TABLE `felix_test`.`utilisateur`;
ALTER TABLE `utilisateur` auto_increment = 1;
# Suppression des données de la table transporteur
TRUNCATE TABLE `felix_test`.`transporteur`;
ALTER TABLE `transporteur` auto_increment = 1;
# Suppression des données de la table methode_livraison
TRUNCATE TABLE `felix_test`.`methode_livraison`;
ALTER TABLE `methode_livraison` auto_increment = 1;