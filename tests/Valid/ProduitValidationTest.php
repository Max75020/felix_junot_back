<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Tva;

class ProduitValidationTest extends KernelTestCase
{
	private $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	public function getValidationErrors(Produit $produit)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($produit);
	}

	private function initializeValidProduit(): Produit
	{
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
		$produit->setPrixHt(19.99);
		$produit->addCategorie($categorie);

		return $produit;
	}

	// Test de validation avec un produit valide
	public function testProduitValide()
	{
		$produit = $this->initializeValidProduit();

		$errors = $this->getValidationErrors($produit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	public function testReferenceUnique()
	{
		// Crée un premier produit avec une référence valide
		$produit = $this->initializeValidProduit();
		$this->entityManager->persist($produit);
		$this->entityManager->flush();
	
		// Crée un second produit avec la même référence que le premier
		$produitDuplique = $this->initializeValidProduit();
		$produitDuplique->setReference($produit->getReference()); // Duplique la référence du premier produit
		$produitDuplique->setNom('Produit test duplicata');
	
		// Valide le second produit avec la même référence
		$errors = $this->getValidationErrors($produitDuplique);
	
		// Vérification de l'erreur d'unicité
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Cette référence est déjà utilisée.", $errors[0]->getMessage());
	}
	

	// Test de validation lorsque le nom est absent
	public function testNomObligatoire()
	{
		$produit = $this->initializeValidProduit(); // Référence valide
		// Suppression du nom
		$produit->setNom("");
		$produit->setReference("REF260920241534");

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le nom est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le prix est négatif
	public function testPrixNegatif()
	{
		$produit = $this->initializeValidProduit(); // Référence valide
		$produit->setPrixHt(-10.00); // Prix négatif
		$produit->setReference("REF260920241530");

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le prix doit être un nombre positif.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la description est absente
	public function testDescriptionObligatoire()
	{
		$produit = $this->initializeValidProduit(); // Référence valide
		// Suppression de la description
		$produit->setDescription("");
		$produit->setReference("REF260920241531");

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La description est obligatoire.", $errors[0]->getMessage());
	}
}
