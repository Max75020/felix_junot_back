<?php

namespace App\State;

use App\Entity\Commande;
use App\Entity\EtatCommande;
use App\Entity\HistoriqueEtatCommande;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CommandeProcessor implements ProcessorInterface
{
	private Security $security;
	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;

	// Constructeur pour injecter la sécurité, l'EntityManager et le Logger
	public function __construct(Security $security, EntityManagerInterface $entityManager, LoggerInterface $logger)
	{
		$this->security = $security;
		$this->entityManager = $entityManager;
		$this->logger = $logger;
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
		if (!$data instanceof Commande) {
			return $data;
		}

		// Log de démarrage du processus
		$this->logger->info("Process method called for Commande ID: {$data->getIdCommande()}");

		// Récupérer l'utilisateur connecté
		$currentUser = $this->security->getUser();

		// Vérifier si c'est une nouvelle commande
		$isNewCommande = $data->getIdCommande() === null;

		// Si l'utilisateur lié à la commande est null, assigner l'utilisateur connecté ou spécifié
		if ($data->getUtilisateur() === null) {
			if (!$this->security->isGranted('ROLE_ADMIN')) {
				$data->setUtilisateur($currentUser);
			} else {
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

		// Récupérer les données originales avant modification
		$unitOfWork = $this->entityManager->getUnitOfWork();
		$originalData = $unitOfWork->getOriginalEntityData($data);

		// Récupérer l'état original
		$originalEtatCommande = $originalData['etat_commande'] ?? null;

		// Récupérer le nouvel état
		$nouvelEtatCommande = $data->getEtatCommande();

		// Log des données originales et nouvelles
		$originalEtatId = $originalEtatCommande ? $originalEtatCommande->getIdEtatCommande() : 'null';
		$nouvelEtatId = $nouvelEtatCommande ? $nouvelEtatCommande->getIdEtatCommande() : 'null';
		$this->logger->info("Original EtatCommande ID: {$originalEtatId}");
		$this->logger->info("Nouvel EtatCommande ID: {$nouvelEtatId}");

		// Log pour débogage
		$this->logger->info("Traitement de la commande ID {$data->getIdCommande()}");

		// Si ce n'est pas une nouvelle commande et que l'état a changé, créer un historique
		if (!$isNewCommande && $originalEtatCommande) {
			$originalEtatCommandeId = $originalEtatCommande->getIdEtatCommande();
			$nouvelEtatCommandeId = $nouvelEtatCommande->getIdEtatCommande();

			$this->logger->info("État original ID: {$originalEtatCommandeId}, Nouvel état ID: {$nouvelEtatCommandeId}");

			if ($originalEtatCommandeId !== $nouvelEtatCommandeId) {
				// Créer une nouvelle entrée dans l'historique
				$historiqueEtatCommande = new HistoriqueEtatCommande();
				$historiqueEtatCommande->setCommande($data);
				$historiqueEtatCommande->setDateEtat(new \DateTime());
				$historiqueEtatCommande->setEtatCommande($nouvelEtatCommande);
				$this->entityManager->persist($historiqueEtatCommande);

				$this->logger->info("Changement d'état détecté pour la commande ID {$data->getIdCommande()} : de ID {$originalEtatCommandeId} à ID {$nouvelEtatCommandeId}.");
			} else {
				$this->logger->info("Aucun changement d'état détecté pour la commande ID {$data->getIdCommande()}.");
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

			$this->logger->info("Historique créé pour la nouvelle commande ID {$data->getIdCommande()} avec l'état ID {$data->getEtatCommande()->getIdEtatCommande()}.");
		}

		// Persister la commande dans la base de données
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		// Retourner l'objet Commande traité
		return $data;
	}
}
