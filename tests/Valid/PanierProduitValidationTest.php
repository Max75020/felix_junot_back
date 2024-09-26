<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\PanierProduit;
use App\Entity\Produit;
use App\Entity\Panier;
use App\Entity\Utilisateur;

class PanierProduitValidationTest extends KernelTestCase
{
	private $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	// Fonction pour obtenir les erreurs de validation
	public function getValidationErrors(PanierProduit $panierProduit)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($panierProduit);
	}

	// Fonction pour initialiser un PanierProduit valide
	private function initializeValidPanierProduit(): PanierProduit
	{
		// 1. Création ou récupération du Produit
		$produitRepository = $this->entityManager->getRepository(Produit::class);
		$produit = $produitRepository->findOneBy(['nom' => 'Produit Test']);
		if (!$produit) {
			$produit = new Produit();
			$produit->setReference('REF' . uniqid());
			$produit->setNom('Produit Test');
			$produit->setDescription('Description test');
			$produit->setPrix(19.99);
			$produit->setStock(100);
			// Définissez les autres propriétés requises du Produit
			$this->entityManager->persist($produit);
			$this->entityManager->flush();
		}

		// 2. Création d'un nouvel Utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		$utilisateur->setPassword('ValidPassw0rd!');
		$utilisateur->setRole('ROLE_USER');
		// Définissez les autres propriétés si nécessaire
		$this->entityManager->persist($utilisateur);
		$this->entityManager->flush();

		// 3. Création d'un nouveau Panier associé à l'Utilisateur
		$panier = new Panier();
		$panier->setUtilisateur($utilisateur);
		// Définissez les autres propriétés si nécessaire
		$this->entityManager->persist($panier);
		$this->entityManager->flush();

		// 4. Création du PanierProduit
		$panierProduit = new PanierProduit();
		$panierProduit->setProduit($produit);
		$panierProduit->setPanier($panier);
		$panierProduit->setQuantite(3); // Quantité valide

		return $panierProduit;
	}

	// Test de validation avec des données valides
	public function testPanierProduitValide()
	{
		$panierProduit = $this->initializeValidPanierProduit();

		$errors = $this->getValidationErrors($panierProduit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	// Test de validation lorsque le produit est absent
	public function testProduitObligatoire()
	{
		$panierProduit = $this->initializeValidPanierProduit();
		$panierProduit->setProduit(null); // Supprime le produit

		$errors = $this->getValidationErrors($panierProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le produit est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le panier est absent
	public function testPanierObligatoire()
	{
		$panierProduit = $this->initializeValidPanierProduit();
		$panierProduit->setPanier(null); // Supprime le panier

		$errors = $this->getValidationErrors($panierProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le panier est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la quantité est négative
	public function testQuantiteNegative()
	{
		$panierProduit = $this->initializeValidPanierProduit();
		$panierProduit->setQuantite(-5); // Quantité invalide

		$errors = $this->getValidationErrors($panierProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La quantité doit être un nombre positif.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la quantité est de 0
	public function testQuantiteNulle()
	{
		$panierProduit = $this->initializeValidPanierProduit();
		$panierProduit->setQuantite(0); // Quantité invalide

		$errors = $this->getValidationErrors($panierProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La quantité doit être un nombre positif.", $errors[0]->getMessage());
	}
}
