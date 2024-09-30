<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Panier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class PanierValidationTest extends KernelTestCase
{
	private ?EntityManagerInterface $entityManager = null;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$this->entityManager->close();
		// Évite les fuites de mémoire
		$this->entityManager = null;
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
		// Création d'un nouvel utilisateur pour le test
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		$utilisateur->setPassword('ValidPassw0rd!');
		$utilisateur->setRoles(['ROLE_USER']);
		// Définissez les autres propriétés requises si nécessaire

		// Persister l'utilisateur
		$this->entityManager->persist($utilisateur);
		$this->entityManager->flush();

		// Créer un Panier valide
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
