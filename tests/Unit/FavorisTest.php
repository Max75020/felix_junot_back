<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Favoris;
use App\Entity\Utilisateur;
use App\Entity\Produit;

class FavorisTest extends TestCase
{
	public function testGettersAndSetters()
	{
		$favoris = new Favoris();
		$utilisateur = new Utilisateur();
		$produit = new Produit();

		$favoris->setUtilisateur($utilisateur);
		$favoris->setProduit($produit);

		$this->assertSame($utilisateur, $favoris->getUtilisateur());
		$this->assertSame($produit, $favoris->getProduit());
	}
}
