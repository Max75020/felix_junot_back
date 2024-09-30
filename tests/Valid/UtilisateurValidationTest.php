<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;

class UtilisateurValidationTest extends KernelTestCase
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

	// Fonction pour obtenir les erreurs de validation d'un utilisateur
	public function getValidationErrors(Utilisateur $utilisateur)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($utilisateur);
	}

	// Fonction qui initialise un Utilisateur avec des valeurs valides
	public function initializeValidUtilisateur(): Utilisateur
	{
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		// Générer un email unique
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		// Mot de passe valide
		$utilisateur->setPassword('ValidPassw0rd75!');
		$utilisateur->setRoles(['ROLE_USER']);
		return $utilisateur;
	}

	// Test de validation avec un utilisateur valide
	public function testUtilisateurValide()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$errors = $this->getValidationErrors($utilisateur);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	// Test de validation lorsque le prénom est absent
	public function testPrenomObligatoire()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPrenom(''); // Supprime le prénom

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le prénom est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le nom est absent
	public function testNomObligatoire()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setNom(''); // Supprime le nom

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le nom est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque l'email est absent ou invalide
	public function testEmailObligatoire()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setEmail(''); // Supprime l'email

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("L'email est obligatoire.", $errors[0]->getMessage());
	}

	// Test mot de passe trop court
	public function testPasswordTropCourt()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPassword('short1!'); // Mot de passe trop court

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le mot de passe doit comporter au moins 12 caractères.", $errors[0]->getMessage());
	}

	// Test mot de passe sans majuscule
	public function testPasswordSansMajuscule()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPassword('lowercase123!'); // Pas de majuscule

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le mot de passe doit contenir au moins une lettre majuscule.", $errors[0]->getMessage());
	}

	// Test mot de passe sans minuscule
	public function testPasswordSansMinuscule()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPassword('UPPERCASE123!'); // Pas de minuscule

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le mot de passe doit contenir au moins une lettre minuscule.", $errors[0]->getMessage());
	}

	// Test mot de passe sans chiffre
	public function testPasswordSansChiffre()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPassword('NoNumbersHere!'); // Pas de chiffre

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le mot de passe doit contenir au moins un chiffre.", $errors[0]->getMessage());
	}

	// Test mot de passe sans caractère spécial
	public function testPasswordSansCaractereSpecial()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPassword('NoSpecialChar123'); // Pas de caractère spécial

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le mot de passe doit contenir au moins un caractère spécial.", $errors[0]->getMessage());
	}

	// Test mot de passe valide
	public function testPasswordValide()
	{
		$utilisateur = $this->initializeValidUtilisateur();
		$utilisateur->setPassword('ValidPassw0rd!'); // Mot de passe valide

		$errors = $this->getValidationErrors($utilisateur);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	// Test de validation pour l'unicité de l'email avec un email déjà présent en BDD
	public function testEmailUnique()
	{
		// Générer un email unique pour le test
		$emailUnique = 'test.' . uniqid() . '@example.com';

		// Créer un premier utilisateur avec cet email
		$utilisateur1 = $this->initializeValidUtilisateur();
		$utilisateur1->setEmail($emailUnique);

		$this->entityManager->persist($utilisateur1);
		$this->entityManager->flush();

		// Créer un second utilisateur avec le même email
		$utilisateur2 = $this->initializeValidUtilisateur();
		$utilisateur2->setEmail($emailUnique);

		// Valider le second utilisateur
		$errors = $this->getValidationErrors($utilisateur2);

		// Vérifier qu'il y a une erreur sur l'unicité de l'email
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Cet email est déjà utilisé.", $errors[0]->getMessage());
	}
}
