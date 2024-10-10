<?php

namespace App\State;

use App\Entity\MethodeLivraison;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

class MethodeLivraisonProcessor implements ProcessorInterface
{
	private EntityManagerInterface $entityManager;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if (!$data instanceof MethodeLivraison) {
			return $data;
		}

		// Recherche une méthode de livraison existante avec le même nom et transporteur
		$existingMethodeLivraison = $this->entityManager->getRepository(MethodeLivraison::class)
			->findOneBy(['nom' => $data->getNom(), 'transporteur' => $data->getTransporteur()]);

		if ($existingMethodeLivraison) {
			// Si elle existe déjà, mettre à jour les champs modifiables
			$existingMethodeLivraison->setDescription($data->getDescription());
			$existingMethodeLivraison->setPrix($data->getPrix());
			$existingMethodeLivraison->setDelaiEstime($data->getDelaiEstime());

			// Persister les modifications
			$this->entityManager->flush();

			return $existingMethodeLivraison;
		}

		// Sinon, créer une nouvelle méthode de livraison
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}
}
