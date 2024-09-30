<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\HistoriqueEtatCommande;
use App\Entity\Commande;
use App\Entity\EtatCommande;
use App\Entity\Utilisateur;
use App\Entity\CommandeProduit;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Tva;

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

		// Création d'un utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		// Générer un email unique
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		// Mot de passe valide
		$utilisateur->setPassword('ValidPassw0rd75!');
		$utilisateur->setRoles(['ROLE_USER']);

		// Création ou récupération de la catégorie
		$categorie = $this->entityManager->getRepository(Categorie::class)->findOneBy(['nom' => 'Catégorie Test']);
		if (!$categorie) {
			$categorie = new Categorie();
			$categorie->setNom('Catégorie Test');
			$this->entityManager->persist($categorie);
			$this->entityManager->flush();
		}

		// Création ou récupération de la TVA
		$tva = $this->entityManager->getRepository(Tva::class)->findOneBy(['taux' => 20.0]);
		if (!$tva) {
			$tva = new Tva();
			$tva->setTaux(20.0);
			$this->entityManager->persist($tva);
			$this->entityManager->flush();
		}
		
		$produit = new Produit();
		// Initialisation du produit avec des valeurs valides
		$produit->setTva($tva);
		// Référence valide basée sur la date actuelle + 4 chiffres aléatoires
		$produit->setReference($produit->generateProductReference());
		$produit->setNom('Produit Test');
		$produit->setDescription('Description test');
		$produit->setPrix(19.99);
		$produit->addCategorie($categorie);

        // Création de la Commande
        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);
        $commande->setDateCommande(new \DateTime());
        $commande->setTotal(24.98);
        $commande->setEtatCommande($etatCommande);
        $commande->setTransporteur('Colissimo');
        $commande->setPoids(1.5);
        $commande->setFraisLivraison(4.99);
        $commande->setNumeroSuivi('1234567810');
        $commande->generateReference();

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
