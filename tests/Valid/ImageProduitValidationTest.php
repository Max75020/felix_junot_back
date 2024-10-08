<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\ImageProduit;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Tva;

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
		$this->entityManager->persist($produit);
		$this->entityManager->flush();

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
