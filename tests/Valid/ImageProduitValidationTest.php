<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\ImageProduit;
use App\Entity\Produit;

class ImageProduitValidationTest extends KernelTestCase
{
	private $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	// Fonction pour obtenir les erreurs de validation
	public function getValidationErrors(ImageProduit $imageProduit)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($imageProduit);
	}

	// Fonction pour initialiser une ImageProduit avec les données présentes en base
	private function initializeValidImageProduit(): ImageProduit
	{
		// Récupère le produit avec id_produit = 1
		$produit = $this->entityManager->getRepository(Produit::class)->find(1);

		if (!$produit) {
			$this->fail('Produit non trouvé.');
		}

		// Crée une ImageProduit valide
		$imageProduit = new ImageProduit();
		$imageProduit->setProduit($produit);
		$imageProduit->setPosition(0);
		$imageProduit->setCover(false);
		$imageProduit->setLegend('Image de produit test');

		return $imageProduit;
	}

	// Test de validation avec des données valides
	public function testImageProduitValide()
	{
		$imageProduit = $this->initializeValidImageProduit();

		$errors = $this->getValidationErrors($imageProduit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	// Test de validation lorsque le produit est absent
	public function testProduitObligatoire()
	{
		$imageProduit = $this->initializeValidImageProduit();
		$imageProduit->setProduit(null); // Supprime le produit

		$errors = $this->getValidationErrors($imageProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le produit est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la position est négative
	public function testPositionNegative()
	{
		$imageProduit = $this->initializeValidImageProduit();
		$imageProduit->setPosition(-1); // Définit une position négative

		$errors = $this->getValidationErrors($imageProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La position doit être un nombre positif ou zéro.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la position est absente (devrait prendre la valeur par défaut)
	public function testPositionDefaut()
	{
		$imageProduit = $this->initializeValidImageProduit();
		$imageProduit->setPosition(0); // La position par défaut est 0

		$errors = $this->getValidationErrors($imageProduit);
		$this->assertCount(0, $errors); // Aucune erreur attendue, car 0 est une valeur valide
	}


	// Test de validation lorsque la légende est absente
	public function testLegendObligatoire()
	{
		$imageProduit = $this->initializeValidImageProduit();
		$imageProduit->setLegend(''); // Supprime la légende

		$errors = $this->getValidationErrors($imageProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La légende est obligatoire.", $errors[0]->getMessage());
	}
}
