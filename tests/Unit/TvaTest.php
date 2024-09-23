<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Tva;

class TvaTest extends TestCase
{
	public function testTauxGetterSetter()
	{
		$tva = new Tva();
		$tva->setTaux('20.00');
		$this->assertEquals('20.00', $tva->getTaux());
	}
}
