<?php

namespace App\Tests\Unit;

use App\Entity\PanierProduit;
use App\Entity\Produit;
use App\Entity\Panier;
use PHPUnit\Framework\TestCase;

class PanierProduitTest extends TestCase
{
	public function testSettersAndGetters()
	{
		$panierProduit = new PanierProduit();
		$produit = new Produit();
		$panier = new Panier();

		// Test des setters
		$panierProduit->setProduit($produit);
		$panierProduit->setPanier($panier);
		$panierProduit->setQuantite(5);

		// Vérification des getters
		$this->assertSame($produit, $panierProduit->getProduit());
		$this->assertSame($panier, $panierProduit->getPanier());
		$this->assertEquals(5, $panierProduit->getQuantite());
	}
}
