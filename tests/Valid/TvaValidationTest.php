<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Tva;

class TvaValidationTest extends KernelTestCase
{
	private function getValidationErrors(Tva $tva)
	{
		self::bootKernel();
		$validator = self::getContainer()->get('validator');
		return $validator->validate($tva);
	}

	// Initialisation d'une TVA valide
	private function initializeValidTva(): Tva
	{
		$tva = new Tva();
		$tva->setTaux('20.00');
		return $tva;
	}

	// Test de validation d'un taux de TVA valide
	public function testTvaValide()
	{
		$tva = $this->initializeValidTva();
		$errors = $this->getValidationErrors($tva);
		$this->assertCount(0, $errors);
	}

	// Test de validation lorsque le taux est négatif
	public function testTauxNegatif()
	{
		$tva = $this->initializeValidTva();
		$tva->setTaux('-5.00'); // Taux négatif

		$errors = $this->getValidationErrors($tva);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le taux de TVA doit être un nombre positif ou zéro.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le taux dépasse 100
	public function testTauxSuperieur100()
	{
		$tva = $this->initializeValidTva();
		$tva->setTaux('150.00'); // Taux au-dessus de 100

		$errors = $this->getValidationErrors($tva);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le taux de TVA doit être compris entre 0 et 100.", $errors[0]->getMessage());
	}
}
