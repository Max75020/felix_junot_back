<?php

namespace App\Enum;

enum EtatPanier: string
{
	case OUVERT = 'ouvert';
	case FERME = 'ferme';

	// Méthode pour récupérer les valeurs possibles
	public static function getValues(): array
	{
		return array_column(self::cases(), 'value');
	}
}
