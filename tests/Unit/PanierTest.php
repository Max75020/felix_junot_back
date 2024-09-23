<?php

namespace App\Tests\Unit;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class PanierTest extends TestCase
{
	public function testSettersAndGetters()
	{
		$panier = new Panier();
		$utilisateur = new Utilisateur();

		// Test des setters
		$panier->setUtilisateur($utilisateur);

		// Vérification des getters
		$this->assertSame($utilisateur, $panier->getUtilisateur());
	}
}
