<?php

namespace App\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Metadata\Operation;
use App\Entity\Commande;
use Psr\Log\LoggerInterface;

class CommandeExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
	private Security $security;
	private LoggerInterface $logger;

	public function __construct(Security $security, LoggerInterface $logger)
	{
		$this->security = $security;
		$this->logger = $logger;
	}

	public function applyToCollection(
		QueryBuilder $queryBuilder,
		QueryNameGeneratorInterface $queryNameGenerator,
		string $resourceClass,
		Operation $operation = null,
		array $context = []
	): void {
		$this->addWhere($queryBuilder, $resourceClass, $operation);
	}

	public function applyToItem(
		QueryBuilder $queryBuilder,
		QueryNameGeneratorInterface $queryNameGenerator,
		string $resourceClass,
		array $identifiers,
		Operation $operation = null,
		array $context = []
	): void {
		$this->addWhere($queryBuilder, $resourceClass, $operation);
	}

	private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
	{
		if (Commande::class !== $resourceClass) {
			return;
		}

		// On récupère l'utilisateur connecté
		$user = $this->security->getUser();

		if ($this->security->isGranted('ROLE_ADMIN')) {
			// Si l'utilisateur est un administrateur, pas de restriction
			return;
		}

		$this->logger->info('CommandeExtension invoked for resource: ' . $resourceClass);

		// Si l'utilisateur est un simple utilisateur, on filtre les commandes par utilisateur
		if ($user) {
			$rootAlias = $queryBuilder->getRootAliases()[0];
			$queryBuilder->andWhere(sprintf('%s.utilisateur = :current_user', $rootAlias))
				->setParameter('current_user', $user);
		} else {
			// Si aucun utilisateur n'est connecté, ne rien retourner
			$queryBuilder->andWhere('1 = 0');
		}
	}
}
