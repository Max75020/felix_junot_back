<?php

namespace App\State;

use App\Entity\Commande;
use App\Entity\EtatCommande;
use App\Entity\HistoriqueEtatCommande;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

class CommandeProcessor implements ProcessorInterface
{
	private $security;
	private $entityManager;

	// Constructeur pour injecter la sécurité et l'EntityManager
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
		// Vérifier si l'objet est bien une instance de Commande
		if ($data instanceof Commande) {
			// Récupérer l'utilisateur connecté
			$currentUser = $this->security->getUser();

			// Vérifier si c'est une nouvelle commande
			$isNewCommande = $data->getIdCommande() === null;

			// Si l'utilisateur lié à la commande est null, assigner l'utilisateur connecté ou spécifié
			if ($data->getUtilisateur() === null) {
				// Pour un utilisateur non-admin, associer la commande à l'utilisateur actuel
				if (!$this->security->isGranted('ROLE_ADMIN')) {
					$data->setUtilisateur($currentUser);
				} else {
					// Pour un administrateur, permettre de spécifier un utilisateur ou utiliser l'utilisateur actuel
					$specifiedUser = $data->getUtilisateur();
					$data->setUtilisateur($specifiedUser ?? $currentUser);
				}
			}

			// Si la référence de la commande n'existe pas encore, la générer
			if ($data->getReference() === null) {
				$data->generateReference();
			}

			// Si l'état de la commande n'est pas défini, associer un état par défaut
			if ($data->getEtatCommande() === null) {
				// Chercher l'état "En attente de paiement" dans la base de données
				$etatCommande = $this->entityManager->getRepository(EtatCommande::class)
					->findOneBy(['libelle' => 'En attente de paiement']);

				// Si l'état existe, l'associer à la commande
				if ($etatCommande) {
					$data->setEtatCommande($etatCommande);
				} else {
					// Si l'état n'existe pas, le créer et l'associer à la commande
					$etatCommande = new EtatCommande();
					$etatCommande->setLibelle('En attente de paiement');
					$this->entityManager->persist($etatCommande);
					$data->setEtatCommande($etatCommande);
				}
			}

			// Si c'est une nouvelle commande, créer une nouvelle entrée dans l'historique
			if ($isNewCommande) {
				// Créer l'historique avec l'état initial et la date actuelle
				$historiqueEtatCommande = new HistoriqueEtatCommande();
				$historiqueEtatCommande->setCommande($data);
				$historiqueEtatCommande->setDateEtat(new \DateTime());
				$historiqueEtatCommande->setEtatCommande($data->getEtatCommande());
				$this->entityManager->persist($historiqueEtatCommande);
			} else {
				// Si la commande existe déjà, vérifier si l'état de la commande a changé
				$oldCommande = $this->entityManager->getRepository(Commande::class)->find($data->getIdCommande());

				// Si l'état de la commande a changé, créer une nouvelle entrée dans l'historique
				if ($oldCommande && $oldCommande->getEtatCommande() !== $data->getEtatCommande()) {
					$historiqueEtatCommande = new HistoriqueEtatCommande();
					$historiqueEtatCommande->setCommande($data);
					$historiqueEtatCommande->setDateEtat(new \DateTime());
					$historiqueEtatCommande->setEtatCommande($data->getEtatCommande());
					$this->entityManager->persist($historiqueEtatCommande);
				}
			}
		}

		// Persister la commande dans la base de données
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		// Retourner l'objet Commande traité
		return $data;
	}
}
