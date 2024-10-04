<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Commande;
use App\Entity\Utilisateur;

final class CurrentUserFilter extends AbstractFilter
{
	private Security $security;

	public function __construct(ManagerRegistry $managerRegistry, Security $security, array $properties = null)
	{
		parent::__construct($managerRegistry, null, $properties);
		$this->security = $security;
	}

	protected function filterProperty(
		string $property,
		$value,
		QueryBuilder $queryBuilder,
		QueryNameGeneratorInterface $queryNameGenerator,
		string $resourceClass,
		Operation $operation = null,
		array $context = []
	): void {
		// Appliquer le filtre uniquement à l'entité Commande et à la propriété 'utilisateur'
		if ($resourceClass !== Commande::class || $property !== 'utilisateur') {
			return;
		}

		// Si l'utilisateur n'est pas administrateur, filtrer par utilisateur connecté
		if (!$this->security->isGranted('ROLE_ADMIN')) {
			$user = $this->security->getUser();
			if ($user instanceof Utilisateur) {
				$rootAlias = $queryBuilder->getRootAliases()[0];
				$parameterName = $queryNameGenerator->generateParameterName('user');
				$queryBuilder
					->andWhere(sprintf('%s.utilisateur = :%s', $rootAlias, $parameterName))
					->setParameter($parameterName, $user);
			}
		}
	}

	public function getDescription(string $resourceClass): array
	{
		return [];
	}
}
