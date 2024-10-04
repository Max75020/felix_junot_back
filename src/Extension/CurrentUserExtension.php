<?php

namespace App\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Metadata\Operation;
use App\Entity\Utilisateur;
use Psr\Log\LoggerInterface;

class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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

	private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, ?Operation $operation): void
	{
		if ($resourceClass !== Utilisateur::class) {
			return;
		}

		// Ignorer l'opération personnalisée 'get_current_user'
		if ($operation && $operation->getName() === 'get_current_user') {
			$this->logger->info('CurrentUserExtension skipped for operation: get_current_user');
			return;
		}

		$this->logger->info('CurrentUserExtension invoked for resource: ' . $resourceClass);

		/** @var Utilisateur|null $user */
		$user = $this->security->getUser();
		// Vérifier si l'utilisateur retourné est bien une instance de Utilisateur
		if (!$user || !$user instanceof Utilisateur) {
			$this->logger->warning('No user found or user is not instance of Utilisateur.');
			return;
		}

		// Si l'utilisateur a le rôle ROLE_ADMIN, ne pas appliquer le filtre
		if ($this->security->isGranted('ROLE_ADMIN')) {
			$this->logger->info('User has ROLE_ADMIN, skipping CurrentUserExtension filter');
			return;
		}

		$userId = $user->getIdUtilisateur();

		$this->logger->info('User found: ' . $userId);

		// Modifier la requête pour filtrer par utilisateur connecté
		$rootAlias = $queryBuilder->getRootAliases()[0];
		$queryBuilder->andWhere("$rootAlias.id_utilisateur = :current_user")
			->setParameter('current_user', $userId);
	}
}
