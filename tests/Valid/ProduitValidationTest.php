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

	private function initializeValidProduit(string $reference = 'REF123'): Produit
	{
		$produit = new Produit();
		$categorie = $this->entityManager->getRepository(Categorie::class)->find(1); // Catégorie Test déjà présente en BDD
		$tva = $this->entityManager->getRepository(Tva::class)->find(1); // Tva Test déjà présente en BDD

		$produit->setReference($reference)
			->setNom('Produit Test')
			->setDescription('Description test')
			->setPrix(19.99)
			->addCategorie($categorie) // Utilisation de addCategorie pour la relation ManyToMany
			->setTva($tva);

		return $produit;
	}

	// Test de validation avec un produit valide
	public function testProduitValide()
	{
		$produit = $this->initializeValidProduit('REF999'); // Utilisation d'une référence unique

		$errors = $this->getValidationErrors($produit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	public function testReferenceUnique()
	{
		// Crée un premier produit avec une référence fictive
		$produit = $this->initializeValidProduit();
		$produit->setReference('REF1000'); // Données fictives pour tester

		// Crée un second produit avec la même référence
		$produitDuplique = new Produit();
		$produitDuplique->setReference('REF1000'); // Référence dupliquée
		$produitDuplique->setNom('Produit test duplicata');
		$produitDuplique->setDescription('Ceci est un duplicata pour test.');
		$produitDuplique->setPrix(150.00);
		$produitDuplique->addCategorie($produit->getCategories()->first()); // Catégorie fictive
		$produitDuplique->setTva($produit->getTva()); // TVA fictive

		// Valide le second produit avec la même référence
		$errors = $this->getValidationErrors($produitDuplique);

		// Vérification de l'erreur d'unicité
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Cette référence est déjà utilisée.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le nom est absent
	public function testNomObligatoire()
	{
		$produit = $this->initializeValidProduit('REF1010'); // Utilisation d'une autre référence unique
		$produit->setNom(''); // Suppression du nom

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le nom est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le prix est négatif
	public function testPrixNegatif()
	{
		$produit = $this->initializeValidProduit('REF1020'); // Utilisation d'une autre référence unique
		$produit->setPrix(-10.00); // Prix négatif

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le prix doit être un nombre positif.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la description est absente
	public function testDescriptionObligatoire()
	{
		$produit = $this->initializeValidProduit('REF1030'); // Utilisation d'une autre référence unique
		$produit->setDescription(''); // Suppression de la description

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La description est obligatoire.", $errors[0]->getMessage());
	}
}
