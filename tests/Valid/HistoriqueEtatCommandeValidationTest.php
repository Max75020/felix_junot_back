<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\HistoriqueEtatCommande;
use App\Entity\Commande;
use App\Entity\EtatCommande;
use App\Entity\Utilisateur;
use App\Entity\CommandeProduit;
use App\Entity\Produit; // N'oubliez pas d'importer l'entité Produit

class HistoriqueEtatCommandeValidationTest extends KernelTestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    // Fonction pour obtenir les erreurs de validation
    public function getValidationErrors(HistoriqueEtatCommande $historique)
    {
        $validator = self::getContainer()->get('validator');
        return $validator->validate($historique);
    }

    // Fonction pour initialiser un historique valide
    private function initializeValidHistorique(): HistoriqueEtatCommande
    {
        $historique = new HistoriqueEtatCommande();

        // Création ou récupération de l'ÉtatCommande
        $etatCommande = $this->entityManager->getRepository(EtatCommande::class)->findOneBy(['libelle' => 'En attente']);
        if (!$etatCommande) {
            $etatCommande = new EtatCommande();
            $etatCommande->setLibelle('En attente');
            $this->entityManager->persist($etatCommande);
            $this->entityManager->flush();
        }

        // Création ou récupération de l'Utilisateur
        $utilisateur = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => 'john.doe@example.com']);
        if (!$utilisateur) {
            $utilisateur = new Utilisateur();
            $utilisateur->setPrenom('John');
            $utilisateur->setNom('Doe');
            $utilisateur->setEmail('john.doe@example.com');
            $utilisateur->setPassword('ValidPassw0rd!'); // Mot de passe valide
            $utilisateur->setRole('ROLE_USER');
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();
        }

        // Création ou récupération du Produit
        $produit = $this->entityManager->getRepository(Produit::class)->findOneBy(['nom' => 'Produit Test']);
        if (!$produit) {
            $produit = new Produit();
            $produit->setNom('Produit Test');
            $produit->setPrix(9.99);
            // Définissez les autres propriétés requises du Produit
            $this->entityManager->persist($produit);
            $this->entityManager->flush();
        }

        // Création de la Commande
        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);
        $commande->setDateCommande(new \DateTime());
        $commande->setTotal(19.99);
        $commande->setEtatCommande($etatCommande);
        $commande->setTransporteur('Colissimo');
        $commande->setPoids(1.5);
        $commande->setFraisLivraison(4.99);
        $commande->setNumeroSuivi('1234567890');
        $commande->setReference('CMD1234567890');

        // Création du CommandeProduit
        $commandeProduit = new CommandeProduit();
        $commandeProduit->setCommande($commande);
        $commandeProduit->setProduit($produit);
        $commandeProduit->setQuantite(1);

        // Ajout du CommandeProduit à la Commande
        $commande->addCommandeProduit($commandeProduit);

        // Persistance de la Commande (le CommandeProduit sera persisté grâce au cascade)
        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        // Association de la Commande et de l'ÉtatCommande à l'Historique
        $historique->setCommande($commande);
        $historique->setEtatCommande($etatCommande);

        return $historique;
    }

    // Test de validation lorsque tout est valide
    public function testHistoriqueEtatCommandeValide()
    {
        $historique = $this->initializeValidHistorique();

        $errors = $this->getValidationErrors($historique);
        $this->assertCount(0, $errors); // Pas d'erreurs attendues
    }

    // Test de validation lorsque la commande est absente
    public function testCommandeObligatoire()
    {
        $historique = $this->initializeValidHistorique();
        $historique->setCommande(null); // Supprime la commande

        $errors = $this->getValidationErrors($historique);
        $this->assertGreaterThan(0, count($errors));
        $this->assertEquals("La commande est obligatoire.", $errors[0]->getMessage());
    }

    // Test de validation lorsque l'état de la commande est absent
    public function testEtatCommandeObligatoire()
    {
        $historique = $this->initializeValidHistorique();
        $historique->setEtatCommande(null); // Supprime l'état de la commande

        $errors = $this->getValidationErrors($historique);
        $this->assertGreaterThan(0, count($errors));
        $this->assertEquals("L'état de la commande est obligatoire.", $errors[0]->getMessage());
    }
}
