<?php

namespace App\State;

use App\Entity\Commande;
use App\Entity\EtatCommande;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class CommandeProcessor implements ProcessorInterface
{
	private $security;
	private $entityManager;

	public function __construct(Security $security, EntityManagerInterface $entityManager)
	{
		$this->security = $security;
		$this->entityManager = $entityManager;
	}

	/**
	 * Processus de traitement de la commande.
	 * 
	 * @param mixed $data L'objet Commande à traiter
	 * @param Operation $operation L'opération en cours
	 * @param array $uriVariables Les variables d'URI
	 * @param array $context Le contexte de l'opération
	 * 
	 * @return mixed L'objet Commande traité
	 */
	public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if ($data instanceof Commande) {
			$currentUser = $this->security->getUser();

			// Gestion de l'utilisateur lié à la commande
			if ($data->getUtilisateur() === null) {
				// Pour un utilisateur standard
				if (!$this->security->isGranted('ROLE_ADMIN')) {
					$data->setUtilisateur($currentUser);
				}

				// Pour un administrateur
				if ($this->security->isGranted('ROLE_ADMIN')) {
					$specifiedUser = $data->getUtilisateur();
					if ($specifiedUser !== null) {
						$data->setUtilisateur($specifiedUser);
					} else {
						$data->setUtilisateur($currentUser);
					}
				}
			}

			// Associe un état par défaut si non défini
			if ($data->getEtatCommande() === null) {
				$etatCommande = $this->entityManager->getRepository(EtatCommande::class)
					->findOneBy(['libelle' => 'En attente de paiement']);
				if ($etatCommande) {
					$data->setEtatCommande($etatCommande);
				} else {
					throw new \LogicException('L\'état de commande "En attente de paiement" est introuvable.');
				}
			}

			// Génère la référence si elle n'existe pas encore
			if ($data->getReference() === null) {
				$data->generateReference();
			}
		}

		// Persiste la commande dans la base de données
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		// Retourne la commande modifiée
		return $data;
	}
}
