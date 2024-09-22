<?php

namespace App\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Adresse;
use App\Entity\Utilisateur;

class AdresseValidationTest extends KernelTestCase
{
	public function getValidationErrors(Adresse $adresse)
	{
		self::bootKernel();
		$validator = self::getContainer()->get('validator');
		return $validator->validate($adresse);
	}

	// Test de validation d'une adresse valide (utilisée pour les tests suivants)
	private function initializeValidAdresse(): Adresse
	{
		$adresse = new Adresse();
		$adresse->setType('Facturation'); // Ajout d'un type valide
		$adresse->setPrenom('Maxime');
		$adresse->setNom('DUPLAISSY');
		$adresse->setRue('64 rue des Rondeaux');
		$adresse->setCodePostal('75020');
		$adresse->setVille('Paris');
		$adresse->setPays('France');
		$adresse->setTelephone('+33 1 23 45 67 89'); // Facultatif mais valide
		return $adresse;
	}

	// Test de validation du code postal (trop long)
	public function testInvalidCodePostal()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setCodePostal('999999999999999999999'); // Trop long

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le code postal ne peut pas dépasser 20 caractères.", $errors[0]->getMessage());
	}

	// Test du code postal valide
	public function testValidCodePostal()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setCodePostal('75020');

		$errors = $this->getValidationErrors($adresse);
		$this->assertCount(0, $errors); // Pas d'erreurs attendues
	}

	// Test du type d'adresse (valeur invalide)
	public function testInvalidType()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setType('Inconnu'); // Valeur invalide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le type d'adresse doit être 'Facturation' ou 'Livraison'.", $errors[0]->getMessage());
	}

	// Test du type d'adresse (valeur valide)
	public function testValidType()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setType('Facturation'); // Valeur valide

		$errors = $this->getValidationErrors($adresse);
		$this->assertCount(0, $errors); // Pas d'erreurs attendues
	}

	// Test du prénom obligatoire
	public function testMissingPrenom()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setPrenom(''); // Prénom vide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le prénom est obligatoire.", $errors[0]->getMessage());
	}

	// Test du nom obligatoire
	public function testMissingNom()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setNom(''); // Nom vide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le nom est obligatoire.", $errors[0]->getMessage());
	}

	// Test de la rue obligatoire
	public function testMissingRue()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setRue(''); // Rue vide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La rue est obligatoire.", $errors[0]->getMessage());
	}

	// Test de la ville obligatoire
	public function testMissingVille()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setVille(''); // Ville vide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La ville est obligatoire.", $errors[0]->getMessage());
	}

	// Test du pays obligatoire
	public function testMissingPays()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setPays(''); // Pays vide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le pays est obligatoire.", $errors[0]->getMessage());
	}

	// Test du téléphone invalide (mauvais format)
	public function testInvalidTelephone()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setTelephone('123ABC'); // Format invalide

		$errors = $this->getValidationErrors($adresse);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le numéro de téléphone n'est pas valide.", $errors[0]->getMessage());
	}

	// Test du téléphone valide
	public function testValidTelephone()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setTelephone('+33 1 23 45 67 89'); // Format valide

		$errors = $this->getValidationErrors($adresse);
		$this->assertCount(0, $errors); // Pas d'erreurs attendues
	}

	// Test des champs facultatifs
	public function testOptionalFields()
	{
		$adresse = $this->initializeValidAdresse();
		$adresse->setBatiment(null); // Facultatif, peut être nul
		$this->assertNull($adresse->getBatiment());

		$adresse->setAppartement('5A'); // Valide
		$this->assertEquals('5A', $adresse->getAppartement());
	}

	// Test de la relation utilisateur
	public function testUtilisateurRelation()
	{
		$utilisateur = new Utilisateur();
		$utilisateur->setNom('Maxime DUPLAISSY');

		$adresse = $this->initializeValidAdresse();
		$adresse->setUtilisateur($utilisateur);

		$this->assertSame($utilisateur, $adresse->getUtilisateur());
	}
}