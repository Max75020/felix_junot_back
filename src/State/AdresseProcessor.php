<?php

namespace App\State;

use App\Entity\Adresse;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AdresseProcessor implements ProcessorInterface
{
	private $entityManager;
	private $security;
	private $logger;

	public function __construct(EntityManagerInterface $entityManager, Security $security, LoggerInterface $logger)
	{
		$this->entityManager = $entityManager;
		$this->security = $security;
		$this->logger = $logger;
	}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if ($data instanceof Adresse) {
			$currentUser = $this->security->getUser();

			// Gérer la logique d'adresses similaires
			if ($data->isSimilaire()) {
				$this->dupliquerAdressePourFacturation($data, $currentUser);
			}

			// Cas pour un utilisateur standard (non administrateur)
			if (!$this->security->isGranted('ROLE_ADMIN') && $data->getUtilisateur() === null) {
				// Associer automatiquement l'utilisateur connecté
				$data->setUtilisateur($currentUser);
			}

			// Cas pour un administrateur
			if ($this->security->isGranted('ROLE_ADMIN')) {
				$specifiedUser = $data->getUtilisateur();
				if ($specifiedUser !== null) {
					$data->setUtilisateur($specifiedUser);
				}
			}
		}

		// Persister les données dans la base
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}

	/**
	 * Duplique l'adresse de livraison pour créer une adresse de facturation si nécessaire.
	 */
	private function dupliquerAdressePourFacturation(Adresse $adresseLivraison, $currentUser): void
	{
		// Créer une nouvelle adresse en tant qu'adresse de facturation
		$adresseFacturation = clone $adresseLivraison;
		$adresseFacturation->setType('Facturation');
		$adresseFacturation->setUtilisateur($currentUser);

		// Persister la nouvelle adresse de facturation
		$this->entityManager->persist($adresseFacturation);
		$this->entityManager->flush();

		$this->logger->info("Adresse similaire dupliquée pour l'utilisateur : " . $currentUser->getUserIdentifier());
	}
}
