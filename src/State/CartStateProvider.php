<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Panier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CartStateProvider implements ProviderInterface
{
	private $entityManager;
	private $security;

	public function __construct(EntityManagerInterface $entityManager, Security $security)
	{
		$this->entityManager = $entityManager;
		$this->security = $security;
	}

	public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Panier
	{
		// Récupérer l'utilisateur actuellement connecté
		$user = $this->security->getUser();

		if (!$user) {
			return null; // Retourner null si l'utilisateur n'est pas authentifié
		}

		// Rechercher le panier avec l'état "ouvert" de cet utilisateur
		$panier = $this->entityManager->getRepository(Panier::class)->findOneBy([
			'utilisateur' => $user,
			'etat' => 'ouvert',
		]);

		if (!$panier) {
			// Créer un nouveau panier s'il n'existe pas
			$panier = new Panier();
			$panier->setUtilisateur($user);
			$panier->setEtat('ouvert');
			$this->entityManager->persist($panier);
			$this->entityManager->flush();
		}

		return $panier;
	}
}
