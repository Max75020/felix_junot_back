<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\CommandeProduit;
use App\Entity\Produit;
use App\Entity\Commande;

class CommandeProduitValidationTest extends KernelTestCase
{
	// Fonction pour obtenir les erreurs de validation d'une commande-produit
	public function getValidationErrors(CommandeProduit $commandeProduit)
	{
		self::bootKernel();
		$validator = self::getContainer()->get('validator');
		return $validator->validate($commandeProduit);
	}

	// Fonction qui initialise un CommandeProduit avec des valeurs valides
	private function initializeValidCommandeProduit(): CommandeProduit
	{
		$commandeProduit = new CommandeProduit();
		$commandeProduit->setProduit(new Produit());
		$commandeProduit->setCommande(new Commande());
		$commandeProduit->setQuantite(2); // Quantité valide
		return $commandeProduit;
	}

	// Test de validation lorsque la quantité est égale à zéro
	public function testQuantiteEgaleZero()
	{
		$commandeProduit = $this->initializeValidCommandeProduit();
		$commandeProduit->setQuantite(0); // Quantité invalide (zéro)

		$errors = $this->getValidationErrors($commandeProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La quantité doit être positive.", $errors[0]->getMessage());
	}

	// Test de validation lorsque la quantité est négative
	public function testQuantiteNegative()
	{
		$commandeProduit = $this->initializeValidCommandeProduit();
		$commandeProduit->setQuantite(-3); // Quantité invalide (négative)

		$errors = $this->getValidationErrors($commandeProduit);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("La quantité doit être positive.", $errors[0]->getMessage());
	}

	// Test de validation avec une quantité valide
	public function testQuantiteValide()
	{
		$commandeProduit = $this->initializeValidCommandeProduit();
		$commandeProduit->setQuantite(5); // Quantité valide

		$errors = $this->getValidationErrors($commandeProduit);
		$this->assertCount(0, $errors); // Aucun problème attendu
	}
}
