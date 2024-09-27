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

	protected function tearDown(): void
	{
		parent::tearDown();
		$this->entityManager->close();
		$this->entityManager = null;
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
		$etatCommande = new EtatCommande();
		// Exemple d'un libellé valide
		$etatCommande->setLibelle('Etat Test Valide');
		return $etatCommande;
	}


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

	public function testLibelleUnique()
	{
		// Récupère un état de commande déjà présent en base avec le libellé 'En préparation'
		$etatExistant = $this->entityManager->getRepository(EtatCommande::class)->findOneBy(['libelle' => 'En préparation']);
		// Si l'état n'existe pas, on le créé
		if (!$etatExistant) {
			$etatExistant = new EtatCommande();
			$etatExistant->setLibelle('En préparation');
			$this->entityManager->persist($etatExistant);
			$this->entityManager->flush();
		}

		// Vérifie que l'état existe bien en base, sinon échec du test
		$this->assertNotNull($etatExistant, "L'état de commande avec le libellé 'En préparation' n'existe pas en base de données.");

		// Crée un nouvel EtatCommande avec le même libellé que l'état existant
		$etatCommandeDuplique = new EtatCommande();
		$etatCommandeDuplique->setLibelle('En préparation'); // Libellé dupliqué

		// Vérification des erreurs de validation
		$errors = $this->getValidationErrors($etatCommandeDuplique);

		// Vérification de l'erreur d'unicité
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Ce libellé existe déjà.", $errors[0]->getMessage());
	}
}
