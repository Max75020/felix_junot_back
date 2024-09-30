<?php

namespace App\State;

use App\Entity\Adresse;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class AdresseProcessor implements ProcessorInterface
{
	private $entityManager;
	private $security;

	public function __construct(EntityManagerInterface $entityManager, Security $security)
	{
		$this->entityManager = $entityManager;
		$this->security = $security;
	}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if ($data instanceof Adresse) {
			$currentUser = $this->security->getUser();

			// Cas pour un utilisateur standard (non administrateur)
			if (!$this->security->isGranted('ROLE_ADMIN') && $data->getUtilisateur() === null) {
				// Associer automatiquement l'utilisateur connecté
				$data->setUtilisateur($currentUser);
			}

			// Cas pour un administrateur
			if ($this->security->isGranted('ROLE_ADMIN')) {
				// Récupère l'utilisateur spécifié par l'administrateur
				$specifiedUser = $data->getUtilisateur();
				if ($specifiedUser !== null) {
					// Associe l'utilisateur spécifié par l'administrateur
					$data->setUtilisateur($specifiedUser);
				}
			}
		}

		// Persister les données dans la base
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}
}
