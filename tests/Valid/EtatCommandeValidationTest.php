<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\EtatCommande;

class EtatCommandeValidationTest extends KernelTestCase
{
	private $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	// Fonction pour obtenir les erreurs de validation
	public function getValidationErrors(EtatCommande $etatCommande)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($etatCommande);
	}

	// Fonction pour initialiser un EtatCommande valide
	private function initializeValidEtatCommande(): EtatCommande
	{
		// Crée un EtatCommande valide
		$etatCommande = new EtatCommande();
		$etatCommande->setLibelle('En préparation'); // Libellé valide

		return $etatCommande;
	}

	// Test de validation avec un EtatCommande valide
	public function testEtatCommandeValide()
	{
		$etatCommande = $this->initializeValidEtatCommande();

		$errors = $this->getValidationErrors($etatCommande);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}

	// Test de validation lorsque le libellé est absent
	public function testLibelleObligatoire()
	{
		$etatCommande = $this->initializeValidEtatCommande();
		$etatCommande->setLibelle(''); // Libellé vide

		$errors = $this->getValidationErrors($etatCommande);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le libellé est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le libellé est trop long
	public function testLibelleTropLong()
	{
		$etatCommande = $this->initializeValidEtatCommande();
		$etatCommande->setLibelle(str_repeat('a', 51)); // Libellé trop long

		$errors = $this->getValidationErrors($etatCommande);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le libellé ne peut pas dépasser 50 caractères.", $errors[0]->getMessage());
	}

	// Test de validation pour l'unicité du libellé
	public function testLibelleUnique()
	{
		// Crée et persiste un premier EtatCommande valide
		$etatCommande = $this->initializeValidEtatCommande();
		$this->entityManager->persist($etatCommande);
		$this->entityManager->flush();

		// Crée un second EtatCommande avec le même libellé
		$etatCommandeDuplique = new EtatCommande();
		$etatCommandeDuplique->setLibelle('En préparation'); // Libellé dupliqué

		$errors = $this->getValidationErrors($etatCommandeDuplique);

		// Vérification de l'erreur d'unicité
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Ce libellé existe déjà.", $errors[0]->getMessage());
	}
}
