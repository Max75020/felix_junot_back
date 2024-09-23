<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Panier;
use App\Entity\Utilisateur;

class PanierValidationTest extends KernelTestCase
{
	private $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	// Fonction pour obtenir les erreurs de validation
	public function getValidationErrors(Panier $panier)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($panier);
	}

	// Fonction pour initialiser un Panier valide
	private function initializeValidPanier(): Panier
	{
		// Récupère un utilisateur existant avec id_utilisateur = 1
		$utilisateur = $this->entityManager->getRepository(Utilisateur::class)->find(1);

		if (!$utilisateur) {
			$this->fail('Utilisateur non trouvé.');
		}

		// Crée un Panier valide
		$panier = new Panier();
		$panier->setUtilisateur($utilisateur);

		return $panier;
	}

	// Test de validation avec un Panier valide
	public function testPanierValide()
	{
		$panier = $this->initializeValidPanier();

		$errors = $this->getValidationErrors($panier);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	// Test de validation lorsque l'utilisateur est absent
	public function testUtilisateurObligatoire()
	{
		$panier = $this->initializeValidPanier();
		$panier->setUtilisateur(null); // Supprime l'utilisateur

		$errors = $this->getValidationErrors($panier);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("L'utilisateur est obligatoire.", $errors[0]->getMessage());
	}
}
