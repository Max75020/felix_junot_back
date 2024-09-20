<?php

namespace App\State;

use App\Entity\PanierProduit;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class PanierProduitProcessor implements ProcessorInterface
{
	private $security;
	private $entityManager;

	public function __construct(Security $security, EntityManagerInterface $entityManager)
	{
		$this->security = $security;
		$this->entityManager = $entityManager;
	}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		// Vérifier que le PanierProduit est bien lié à un Panier et que l'utilisateur du Panier n'est pas encore défini
		if ($data instanceof PanierProduit && $data->getPanier()->getUtilisateur() === null) {
			// Assigner l'utilisateur connecté au Panier via l'entité PanierProduit
			$data->getPanier()->setUtilisateur($this->security->getUser());
		}

		// Persist les modifications
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}
}
