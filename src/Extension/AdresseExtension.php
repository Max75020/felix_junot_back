<?php

namespace App\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use ApiPlatform\Metadata\Operation;
use App\Entity\Adresse;
use Psr\Log\LoggerInterface;

class AdresseExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
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
		$this->addWhere($queryBuilder, $resourceClass);
	}

	public function applyToItem(
		QueryBuilder $queryBuilder,
		QueryNameGeneratorInterface $queryNameGenerator,
		string $resourceClass,
		array $identifiers,
		Operation $operation = null,
		array $context = []
	): void {
		$this->addWhere($queryBuilder, $resourceClass);
	}

	private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
	{
		// On vérifie que la ressource est bien l'entité Adresse
		if (Adresse::class !== $resourceClass) {
			return;
		}

		// Récupérer l'utilisateur connecté
		$user = $this->security->getUser();

		// Si l'utilisateur est un administrateur, il peut voir toutes les adresses
		if ($this->security->isGranted('ROLE_ADMIN')) {
			return; // Pas de filtre pour les administrateurs
		}

		$this->logger->info('AdresseExtension invoked for resource: ' . $resourceClass);

		// Si l'utilisateur est basique (ROLE_USER), on filtre les adresses pour ne montrer que les siennes
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
