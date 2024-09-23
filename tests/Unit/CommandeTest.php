<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Entity\EtatCommande;

class CommandeTest extends TestCase
{
	public function testGettersAndSetters()
	{
		$commande = new Commande();

		// Test du setter et getter pour utilisateur
		$utilisateur = new Utilisateur();
		$commande->setUtilisateur($utilisateur);
		$this->assertSame($utilisateur, $commande->getUtilisateur());

		// Test du setter et getter pour date de commande
		$dateCommande = new \DateTime();
		$commande->setDateCommande($dateCommande);
		$this->assertSame($dateCommande, $commande->getDateCommande());

		// Test du setter et getter pour le total
		$commande->setTotal('99.99');
		$this->assertEquals('99.99', $commande->getTotal());

		// Test du setter et getter pour l'état de la commande
		$etatCommande = new EtatCommande();
		$commande->setEtatCommande($etatCommande);
		$this->assertSame($etatCommande, $commande->getEtatCommande());

		// Test du setter et getter pour le transporteur
		$commande->setTransporteur('DHL');
		$this->assertEquals('DHL', $commande->getTransporteur());

		// Test du setter et getter pour le poids
		$commande->setPoids('2.5');
		$this->assertEquals('2.5', $commande->getPoids());

		// Test du setter et getter pour les frais de livraison
		$commande->setFraisLivraison('10.00');
		$this->assertEquals('10.00', $commande->getFraisLivraison());

		// Test du setter et getter pour le numéro de suivi
		$commande->setNumeroSuivi('123ABC');
		$this->assertEquals('123ABC', $commande->getNumeroSuivi());
	}
}
