<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\PanierProduit;
use App\Entity\Produit;
use App\Entity\Panier;

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
		// Récupère le produit et le panier avec id = 1
		$produit = $this->entityManager->getRepository(Produit::class)->find(1);
		$panier = $this->entityManager->getRepository(Panier::class)->find(1);

		if (!$produit || !$panier) {
			$this->fail('Produit ou Panier non trouvés.');
		}

		// Crée un PanierProduit valide
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
