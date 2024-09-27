<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Categorie;

class CategorieValidationTest extends KernelTestCase
{

	protected function setUp(): void
	{
		self::bootKernel();
	}

	public function getValidationErrors(Categorie $categorie)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($categorie);
	}

	private function initializeValidCategorie(): Categorie
	{
		$categorie = new Categorie();
		$categorie->setNom('Électronique');
		return $categorie;
	}

	// Test de validation du champ nom (champ obligatoire)
	public function testNomObligatoire()
	{
		// Création d'une catégorie valide avec un nom attribué
		$categorie = $this->initializeValidCategorie();
		// Nom vidé
		$categorie->setNom('');
		// Vérification des erreurs de validation
		$errors = $this->getValidationErrors($categorie);
		// Vérification qu'il y a bien une erreur
		$this->assertGreaterThan(0, count($errors));
		// On vérifie que le message d'erreur est bien celui attendu
		$this->assertEquals("Le nom de la catégorie est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation du nom trop long
	public function testNomTooLong()
	{
		// Création d'une catégorie valide avec un nom attribué
		$categorie = $this->initializeValidCategorie();
		// Nom avec plus de 100 caractères
		$categorie->setNom(str_repeat('A', 101));
		// Vérification des erreurs de validation
		$errors = $this->getValidationErrors($categorie);
		// Vérification qu'il y a bien une erreur
		$this->assertGreaterThan(0, count($errors));
		// On vérifie que le message d'erreur est bien celui attendu
		$this->assertEquals("Le nom de la catégorie ne peut pas dépasser 100 caractères.", $errors[0]->getMessage());
	}

	// Test de validation du nom valide
	public function testValidNom()
	{
		// Création d'une catégorie valide avec un nom attribué
		$categorie = $this->initializeValidCategorie();

		// Vérification des erreurs de validation
		$errors = $this->getValidationErrors($categorie);
		// Aucune erreur ne doit être retournée
		$this->assertCount(0, $errors);
	}
}
