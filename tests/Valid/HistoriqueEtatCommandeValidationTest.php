<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\HistoriqueEtatCommande;
use App\Entity\Commande;
use App\Entity\EtatCommande;

class HistoriqueEtatCommandeValidationTest extends KernelTestCase
{
	private $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	// Fonction pour obtenir les erreurs de validation
	public function getValidationErrors(HistoriqueEtatCommande $historique)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($historique);
	}

	// Fonction pour initialiser un historique valide
	private function initializeValidHistorique(): HistoriqueEtatCommande
	{
		$historique = new HistoriqueEtatCommande();

		// Récupère une commande et un état de commande existants en base (id 1)
		$commande = $this->entityManager->getRepository(Commande::class)->find(1);
		$etatCommande = $this->entityManager->getRepository(EtatCommande::class)->find(1);

		if (!$commande || !$etatCommande) {
			$this->fail('Commande ou ÉtatCommande non trouvé.');
		}

		$historique->setCommande($commande);
		$historique->setEtatCommande($etatCommande);

		return $historique;
	}

	// Test de validation lorsque tout est valide
	public function testHistoriqueEtatCommandeValide()
	{
		$historique = $this->initializeValidHistorique();

		$errors = $this->getValidationErrors($historique);
		$this->assertCount(0, $errors); // Pas d'erreurs attendues
	}

	// Test de validation lorsque la commande est absente
	public function testCommandeObligatoire()
	{
		$historique = $this->initializeValidHistorique();
		$historique->setCommande(null); // Supprime la commande

		$errors = $this->getValidationErrors($historique);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La commande est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque l'état de la commande est absent
	public function testEtatCommandeObligatoire()
	{
		$historique = $this->initializeValidHistorique();
		$historique->setEtatCommande(null); // Supprime l'état de la commande

		$errors = $this->getValidationErrors($historique);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("L'état de la commande est obligatoire.", $errors[0]->getMessage());
	}
}
