<?php

namespace App\State;

use App\Entity\Commande;
use App\Entity\CommandeProduit;
use App\Entity\EtatCommande;
use App\Entity\HistoriqueEtatCommande;
use App\Entity\Panier;
use App\Entity\Adresse;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor pour gérer les opérations sur l'entité Commande.
 * Il gère l'attribution de l'utilisateur, la génération de la référence de commande,
 * la gestion de l'état de la commande, l'historique des changements d'état,
 * et le transfert des données du panier vers la commande.
 */
class CommandeProcessor implements ProcessorInterface
{
	private Security $security;
	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;

	public function __construct(Security $security, EntityManagerInterface $entityManager, LoggerInterface $logger)
	{
		$this->security = $security;
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if (!$data instanceof Commande) {
			return $data;
		}

		// Vérification immédiate du paiement avant de continuer
		if (!$this->isPaymentConfirmed($data)) {
			throw new BadRequestHttpException('Le paiement n\'est pas confirmé, la commande ne peut pas être traitée.');
		}

		// Log de démarrage du processus
		$this->logger->info("Process method called for Commande ID: {$data->getIdCommande()}");
		$currentUser = $this->security->getUser();
		$isNewCommande = $data->getIdCommande() === null;

		// Gérer les adresses similaires
		$this->handleAdressesSimilaires($data);

		// Associer l'utilisateur à la commande si nécessaire
		$this->assignerUtilisateur($data, $currentUser);

		// Générer la référence de la commande si elle n'est pas encore définie
		$this->genererReferenceCommande($data);

		// Assigner l'état par défaut de la commande si aucun n'est défini
		$this->assignerEtatCommandeDefaut($data);

		// Transférer les informations du panier vers la commande
		if ($data->getPanier()) {
			$this->transfertPanierVersCommande($data->getPanier(), $data);
		}

		// Vérifier que le prix total de la commande est correct
		$this->verifierPrixTotalCommande($data);

		// Gérer l'historique des changements d'état de la commande
		$this->gererHistoriqueEtatCommande($data, $isNewCommande);

		$this->entityManager->persist($data);
		$this->entityManager->flush();

		return $data;
	}

	/**
	 * Gère la duplication des adresses si elles sont similaires.
	 */
	private function handleAdressesSimilaires(Commande $commande): void
	{
		$adresseLivraison = $commande->getAdresseLivraison();

		if ($adresseLivraison && $adresseLivraison->isSimilaire()) {
			// Dupliquer l'adresse de livraison pour créer une adresse de facturation
			$adresseFacturation = clone $adresseLivraison;
			$adresseFacturation->setType('Facturation');

			// Persister la nouvelle adresse de facturation
			$this->entityManager->persist($adresseFacturation);
			$this->entityManager->flush();

			// Associer l'adresse de facturation à la commande
			$commande->setAdresseFacturation($adresseFacturation);

			$this->logger->info("Adresse similaire dupliquée pour la commande : " . $commande->getReference());
		}
	}

	private function assignerUtilisateur(Commande $commande, $currentUser): void
	{
		if (!$this->security->isGranted('ROLE_ADMIN')) {
			$commande->setUtilisateur($currentUser);
		} else {
			$specifiedUser = $commande->getUtilisateur();
			$commande->setUtilisateur($specifiedUser ?? $currentUser);
		}
	}

	private function genererReferenceCommande(Commande $commande): void
	{
		if ($commande->getReference() === null) {
			$commande->generateReference();
		}
	}

	private function assignerEtatCommandeDefaut(Commande $commande): void
	{
		$etatCommande = $this->entityManager->getRepository(EtatCommande::class)
			->findOneBy(['libelle' => 'En attente de paiement']) ?? new EtatCommande('En attente de paiement');

		$this->entityManager->persist($etatCommande);
		$commande->setEtatCommande($etatCommande);
	}

	private function isPaymentConfirmed(Commande $commande): bool
	{
		// Cette méthode doit vérifier le statut du paiement
		return $commande->getEtatCommande()->getLibelle() === 'Paiement confirmé';
	}

	/**
	 * Transfère les informations du panier et des produits du panier vers la commande et les commande-produits.
	 */
	private function transfertPanierVersCommande(Panier $panier, Commande $commande): void
	{
		foreach ($panier->getPanierProduits() as $panierProduit) {
			$commandeProduit = new CommandeProduit();
			$commandeProduit->setCommande($commande);
			$commandeProduit->setProduit($panierProduit->getProduit());
			$commandeProduit->setQuantite($panierProduit->getQuantite());
			$commandeProduit->setPrixTotalProduit($panierProduit->getPrixTotalProduit());

			$this->entityManager->persist($commandeProduit);
			$commande->addCommandeProduit($commandeProduit);
		}

		$commande->setTotalProduitsCommande($panier->getPrixTotalPanier());
	}

	private function verifierPrixTotalCommande(Commande $commande): void
	{
		$totalProduits = $commande->getTotalProduitsCommande();
		$fraisLivraison = $commande->getFraisLivraison();
		$prixTotalAttendu = bcadd($totalProduits, $fraisLivraison, 2);

		if ($commande->getPrixTotalCommande() !== $prixTotalAttendu) {
			throw new BadRequestHttpException('Le prix total de la commande ne correspond pas à la somme des produits et des frais de livraison.');
		}
	}

	private function gererHistoriqueEtatCommande(Commande $commande, bool $isNewCommande): void
	{
		$unitOfWork = $this->entityManager->getUnitOfWork();
		$originalData = $unitOfWork->getOriginalEntityData($commande);
		$originalEtatCommande = $originalData['etat_commande'] ?? null;
		$nouvelEtatCommande = $commande->getEtatCommande();

		if ($isNewCommande) {
			$historiqueEtatCommande = new HistoriqueEtatCommande();
			$historiqueEtatCommande->setCommande($commande);
			$historiqueEtatCommande->setDateEtat(new \DateTime());
			$historiqueEtatCommande->setEtatCommande($nouvelEtatCommande);
			$this->entityManager->persist($historiqueEtatCommande);
		} elseif ($originalEtatCommande && $originalEtatCommande->getIdEtatCommande() !== $nouvelEtatCommande->getIdEtatCommande()) {
			$historiqueEtatCommande = new HistoriqueEtatCommande();
			$historiqueEtatCommande->setCommande($commande);
			$historiqueEtatCommande->setDateEtat(new \DateTime());
			$historiqueEtatCommande->setEtatCommande($nouvelEtatCommande);
			$this->entityManager->persist($historiqueEtatCommande);
		}
	}
}
