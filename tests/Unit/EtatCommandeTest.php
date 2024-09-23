<?php

namespace App\Tests\Unit;

use App\Entity\EtatCommande;
use PHPUnit\Framework\TestCase;

class EtatCommandeTest extends TestCase
{
	public function testSettersAndGetters()
	{
		$etatCommande = new EtatCommande();

		// Test des setters
		$etatCommande->setLibelle('En préparation');

		// Vérification des getters
		$this->assertEquals('En préparation', $etatCommande->getLibelle());
	}
}
