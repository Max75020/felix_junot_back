<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Adresse;

class AdresseTest extends TestCase
{
	public function testGettersAndSetters()
	{
		$adresse = new Adresse();

		// Test du setter et getter pour le prénom
		$adresse->setPrenom('Maxime');
		$this->assertEquals('Maxime', $adresse->getPrenom());

		// Test du setter et getter pour le nom
		$adresse->setNom('DUPLAISSY');
		$this->assertEquals('DUPLAISSY', $adresse->getNom());

		// Test du setter et getter pour la rue
		$adresse->setRue('64 rue des Rondeaux');
		$this->assertEquals('64 rue des Rondeaux', $adresse->getRue());

		// Test du setter et getter pour le bâtiment
		$adresse->setBatiment('Batiment A');
		$this->assertEquals('Batiment A', $adresse->getBatiment());

		// Test du setter et getter pour l'appartement
		$adresse->setAppartement('1A');
		$this->assertEquals('1A', $adresse->getAppartement());

		// Test du setter et getter pour le code postal
		$adresse->setCodePostal('75020');
		$this->assertEquals('75020', $adresse->getCodePostal());

		// Test du setter et getter pour la ville
		$adresse->setVille('Paris');
		$this->assertEquals('Paris', $adresse->getVille());

		// Test du setter et getter pour le pays
		$adresse->setPays('France');
		$this->assertEquals('France', $adresse->getPays());

		// Test du setter et getter pour le téléphone
		$adresse->setTelephone('+33 1 23 45 67 89');
		$this->assertEquals('+33 1 23 45 67 89', $adresse->getTelephone());

		// Test du setter et getter pour le champ similaire
		$adresse->setSimilaire(true);
		$this->assertTrue($adresse->isSimilaire());
	}
}
