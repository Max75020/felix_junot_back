<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\CommandeProduit;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Tva;
use App\Entity\Utilisateur;
use App\Entity\EtatCommande;

class CommandeProduitValidationTest extends KernelTestCase
{
	protected function setUp(): void
	{
		self::bootKernel();
	}

	// Fonction pour obtenir les erreurs de validation d'une commande-produit
	public function getValidationErrors(CommandeProduit $commandeProduit)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($commandeProduit);
	}

	// Fonction qui initialise un CommandeProduit avec des valeurs valides
	private function initializeValidCommandeProduit(): CommandeProduit
	{
		// Création d'une TVA
		$tva = new Tva();
		$tva->setTaux(20.0);

		// Initialisation du produit
		$produit = new Produit();
		// Référence valide basée sur la date actuelle + 4 chiffres aléatoires
		$produit->setReference($produit->generateProductReference());
		$produit->setNom('Produit Test');
		$produit->setDescription('Description test');
		$produit->setPrix(19.99);
		$produit->setTva($tva);
		
		// Création d'un utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		$utilisateur->setPassword('ValidPassw0rd!');
		$utilisateur->setRoles(['ROLE_USER']);
		$utilisateur->setEmailValide(true);

		// Création d'un etat de commande
		$etatCommande = new EtatCommande();
		$etatCommande->setLibelle('En attente de paiement');

		// Initialisation de la commande
		$commande = new Commande();
		$commande->setUtilisateur($utilisateur);
		$commande->setEtatCommande($etatCommande);
		$commande->setDateCommande(new \DateTime());
		$commande->setTotal('100.00');
		$commande->setTransporteur('Colissimo');
		$commande->setPoids('2.50');
		$commande->setFraisLivraison('5.00');
		$commande->setNumeroSuivi('ABC123');
		$commande->generateReference();
	
		// Création de CommandeProduit
		$commandeProduit = new CommandeProduit();
		$commandeProduit->setCommande($commande);
		$commandeProduit->setProduit($produit);
		$commandeProduit->setQuantite(2); // Quantité valide
	
		return $commandeProduit;
	}
	

	// Test de validation lorsque la quantité est égale à zéro
	public function testQuantiteEgaleZero()
	{
		$commandeProduit = $this->initializeValidCommandeProduit();
		$commandeProduit->setQuantite(0); // Quantité invalide (zéro)

		$errors = $this->getValidationErrors($commandeProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La quantité doit être positive.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la quantité est négative
	public function testQuantiteNegative()
	{
		$commandeProduit = $this->initializeValidCommandeProduit();
		$commandeProduit->setQuantite(-3); // Quantité invalide (négative)

		$errors = $this->getValidationErrors($commandeProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La quantité doit être positive.", $errors[0]->getMessage());
	}

	// Test de validation avec une quantité valide
	public function testQuantiteValide()
	{
		$commandeProduit = $this->initializeValidCommandeProduit();
		$commandeProduit->setQuantite(5); // Quantité valide

		$errors = $this->getValidationErrors($commandeProduit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}
}
