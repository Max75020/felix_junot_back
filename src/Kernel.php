<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
	use MicroKernelTrait;

	public function boot(): void
	{
		parent::boot();

		// Définit le fuseau horaire par défaut pour l'application
		date_default_timezone_set('Europe/Paris');
	}
}
