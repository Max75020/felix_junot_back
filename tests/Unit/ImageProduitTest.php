<?php

namespace App\Tests\Unit;

use App\Entity\ImageProduit;
use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

class ImageProduitTest extends TestCase
{
	public function testSettersAndGetters()
	{
		$imageProduit = new ImageProduit();
		$produit = new Produit();

		// Test des setters
		$imageProduit->setProduit($produit);
		$imageProduit->setPosition(1);
		$imageProduit->setCover(true);
		$imageProduit->setLegend('Une belle image');

		// Vérification des getters
		$this->assertSame($produit, $imageProduit->getProduit());
		$this->assertEquals(1, $imageProduit->getPosition());
		$this->assertTrue($imageProduit->getCover());
		$this->assertEquals('Une belle image', $imageProduit->getLegend());
	}
}
