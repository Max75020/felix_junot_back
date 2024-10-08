<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\PanierProduit;
use App\Entity\Produit;
use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Entity\Categorie;
use App\Entity\Tva;

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
		// Création ou récupération du Produit
		$produitRepository = $this->entityManager->getRepository(Produit::class);
		$produit = $produitRepository->findOneBy(['nom' => 'Produit Test']);
		if (!$produit) {
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
			// Référence valide aléatoire
			$reference = 'REF' . rand(1000000000000, 9999999999999);

			$produit = new Produit();
			// Initialisation du produit avec des valeurs valides
			$produit->setTva($tva);
			// Référence valide basée sur la date actuelle + 4 chiffres aléatoires
			$produit->setReference($produit->generateProductReference());
			$produit->setNom('Produit Test');
			$produit->setDescription('Description test');
			$produit->setPrixHt(19.99);
			$produit->addCategorie($categorie);
			// Définissez les autres propriétés requises du Produit
			$this->entityManager->persist($produit);
			$this->entityManager->flush();
		}

		// Création d'un nouvel Utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		$utilisateur->setPassword('ValidPassw0rd!');
		$utilisateur->setRoles(['ROLE_USER']);
		// Définissez les autres propriétés si nécessaire
		$this->entityManager->persist($utilisateur);
		$this->entityManager->flush();

		// Création d'un nouveau Panier associé à l'Utilisateur
		$panier = new Panier();
		$panier->setUtilisateur($utilisateur);
		$this->entityManager->persist($panier);
		$this->entityManager->flush();

		// Création du PanierProduit
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
