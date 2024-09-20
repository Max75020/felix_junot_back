<?php

namespace App\State;

use App\Entity\Panier;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class PanierProcessor implements ProcessorInterface
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
		// Si le panier n'a pas d'utilisateur, on lui affecte l'utilisateur connecté
		if ($data instanceof Panier && $data->getUtilisateur() === null) {
			$data->setUtilisateur($this->security->getUser());
		}

		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}
}
