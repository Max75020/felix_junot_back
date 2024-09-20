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
		// Si l'adresse n'a pas d'utilisateur associé
		if ($data instanceof Adresse && $data->getUtilisateur() === null) {
			// Associer l'utilisateur connecté
			$data->setUtilisateur($this->security->getUser());
		}

		// Persister les données dans la base
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}
}
