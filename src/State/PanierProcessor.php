<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Panier;
use App\Enum\EtatPanier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor pour gérer les opérations sur l'entité Panier.
 * Il initialise l'état du panier et vérifie le total des produits calculé.
 */
class PanierProcessor implements ProcessorInterface
{
	private EntityManagerInterface $entityManager;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if ($data instanceof Panier) {
			// Initialiser l'état du panier à OUVERT lors de la création si l'état n'est pas défini
			if (isset($context['operation_name']) && $context['operation_name'] === 'post' && $data->getEtat() === null) {
				$data->setEtat(EtatPanier::OUVERT);
			}

			// Vérifier que le total des produits dans le panier est correct
			$totalProduitCalculé = $context['total_produits'] ?? null;
			if ($totalProduitCalculé !== null && !$data->verifierTotalProduits($totalProduitCalculé)) {
				throw new BadRequestHttpException('Le total des produits dans le panier ne correspond pas au calcul des produits.');
			}
		}

		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}
}
