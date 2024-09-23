<?php

namespace App\Tests\Unit;

use App\Entity\HistoriqueEtatCommande;
use App\Entity\Commande;
use App\Entity\EtatCommande;
use PHPUnit\Framework\TestCase;

class HistoriqueEtatCommandeTest extends TestCase
{
	public function testInitialisationDateAutomatique()
	{
		$historique = new HistoriqueEtatCommande();

		// Vérification que la date est bien initialisée à la création
		$this->assertInstanceOf(\DateTimeInterface::class, $historique->getDateEtat());
	}

	public function testSettersAndGetters()
	{
		$historique = new HistoriqueEtatCommande();
		$commande = new Commande();
		$etatCommande = new EtatCommande();
		$date = new \DateTime();

		// Test des setters
		$historique->setCommande($commande);
		$historique->setEtatCommande($etatCommande);
		$historique->setDateEtat($date);

		// Vérification des getters
		$this->assertSame($commande, $historique->getCommande());
		$this->assertSame($etatCommande, $historique->getEtatCommande());
		$this->assertSame($date, $historique->getDateEtat());
	}
}
