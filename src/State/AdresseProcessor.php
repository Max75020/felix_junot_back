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

			$this->logger->info('AdresseProcessor called.');

			// Gérer la logique d'adresses similaires
			if ($data->isSimilaire()) {
				$this->creerAdresseSimilaire($data, $currentUser);
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
		$this->logger->info('AdresseProcessor finished.');
		
		return $data;
	}

	/**
	 * Créer une adresse similaire selon le type d'adresse initiale (facturation ou livraison).
	 */
	private function creerAdresseSimilaire(Adresse $adresse, $currentUser): void
	{
		// Dupliquer l'adresse en fonction de son type
		$nouvelleAdresse = clone $adresse;

		if ($adresse->getType() === 'Facturation') {
			// Si c'est une adresse de facturation, créer une adresse de livraison similaire
			$nouvelleAdresse->setType('Livraison');
		} elseif ($adresse->getType() === 'Livraison') {
			// Si c'est une adresse de livraison, créer une adresse de facturation similaire
			$nouvelleAdresse->setType('Facturation');
		}

		// Associer l'adresse similaire à l'utilisateur actuel
		$nouvelleAdresse->setUtilisateur($currentUser);

		// Persister la nouvelle adresse similaire
		$this->entityManager->persist($nouvelleAdresse);
		$this->entityManager->flush();

		$this->logger->info("Adresse similaire dupliquée pour l'utilisateur : " . $currentUser->getUserIdentifier() . ", type de la nouvelle adresse : " . $nouvelleAdresse->getType());
	}
}
