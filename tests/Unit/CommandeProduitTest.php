<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\CommandeProduit;
use App\Entity\Produit;
use App\Entity\Commande;

class CommandeProduitTest extends TestCase
{
	// Test des getters et setters pour CommandeProduit
	public function testGettersAndSetters()
	{
		$commandeProduit = new CommandeProduit();

		// Produit
		$produit = new Produit();
		$commandeProduit->setProduit($produit);
		$this->assertSame($produit, $commandeProduit->getProduit());

		// Commande
		$commande = new Commande();
		$commandeProduit->setCommande($commande);
		$this->assertSame($commande, $commandeProduit->getCommande());

		// Quantité
		$commandeProduit->setQuantite(3);
		$this->assertEquals(3, $commandeProduit->getQuantite());
	}
}
