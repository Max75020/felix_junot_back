<?php

namespace App\Tests\Unit;

use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

class ProduitTest extends TestCase
{
	public function testGettersAndSetters()
	{
		$produit = new Produit();

		$produit->setReference('REF123');
		$this->assertEquals('REF123', $produit->getReference());

		$produit->setNom('Produit Test');
		$this->assertEquals('Produit Test', $produit->getNom());

		$produit->setDescription('Description du produit test');
		$this->assertEquals('Description du produit test', $produit->getDescription());

		$produit->setPrix(19.99);
		$this->assertEquals(19.99, $produit->getPrix());
	}
}
