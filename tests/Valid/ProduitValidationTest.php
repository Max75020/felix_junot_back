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

	private function initializeValidProduit(string $reference = 'REF7894561231'): Produit
	{
		$produit = new Produit();

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

		// Initialisation du produit avec des valeurs valides
		$produit->setReference($reference) // Doit être de 13 caractères
			->setNom('Produit Test')
			->setDescription('Description test')
			->setPrix(19.99)
			->addCategorie($categorie)
			->setTva($tva);

		return $produit;
	}

	// Test de validation avec un produit valide
	public function testProduitValide()
	{
		$produit = $this->initializeValidProduit('REF1234567890'); // Référence de 13 caractères

		$errors = $this->getValidationErrors($produit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	public function testReferenceUnique()
	{
		// Référence valide aléatoire
		$reference = 'REF' . rand(1000000000000, 9999999999999);
		// Crée un premier produit avec une référence valide
		$produit = $this->initializeValidProduit($reference);
		$this->entityManager->persist($produit);
		$this->entityManager->flush();

		// Crée un second produit avec la même référence
		$produitDuplique = $this->initializeValidProduit($reference); // Référence dupliquée
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
		$produit = $this->initializeValidProduit('REF1234567893'); // Référence valide
		// Suppression du nom
		$produit->setNom("");

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le nom est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le prix est négatif
	public function testPrixNegatif()
	{
		$produit = $this->initializeValidProduit('REF1234567894'); // Référence valide
		$produit->setPrix(-10.00); // Prix négatif

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le prix doit être un nombre positif.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la description est absente
	public function testDescriptionObligatoire()
	{
		$produit = $this->initializeValidProduit('REF1234567895'); // Référence valide
		// Suppression de la description
		$produit->setDescription("");

		$errors = $this->getValidationErrors($produit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La description est obligatoire.", $errors[0]->getMessage());
	}
}
