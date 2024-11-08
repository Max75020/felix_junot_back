<?php

namespace App\State;

use App\Entity\Adresse;
use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\EtatCommande;
use App\Entity\CommandeProduit;
use App\Entity\HistoriqueEtatCommande;
use App\Service\GestionCommandeService;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\EmailService;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CommandeProcessor implements ProcessorInterface
{
	private Security $security;
	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;
	private GestionCommandeService $gestionCommandeService;
	private EmailService $emailService;

	public function __construct(
		Security $security,
		EntityManagerInterface $entityManager,
		LoggerInterface $logger,
		GestionCommandeService $gestionCommandeService,
		EmailService $emailService
	) {
		$this->security = $security;
		$this->entityManager = $entityManager;
		$this->logger = $logger;
		$this->gestionCommandeService = $gestionCommandeService;
		$this->emailService = $emailService;
	}

	public function process($data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		if (!$data instanceof Commande) {
			return $data;
		}

		// Récupérer l'utilisateur actuel
		$utilisateur = $this->security->getUser();
		// Récupérer le panier
		$panier = $data->getPanier();
		// Récupérer le transporteur de la commande
		$transporteur = $data->getTransporteur();
		// Récupérer la méthode de livraison (qui dépend du transporteur)
		$methodeLivraison = $data->getMethodeLivraison();
		// Récupérer l'adresse de facturation
		$adresseFacturation = $data->getAdresseFacturation();
		// Récupérer l'adresse de livraison
		$adresseLivraison = $data->getAdresseLivraison();
		// Récupérer le poids de la commande (peut être calculé ou fourni)
		$poids = $data->getPoids();
		// Récupérer ou générer le numéro de suivi
		$numeroSuivi = $data->getNumeroSuivi(); // Numéro de suivi par défaut
		// Récupérer l'état de la commande (par exemple, "Commande Payée")
		$etatCommande = $data->getEtatCommande();

		// Vérification du Panier
		if ($panier === null) {
			$this->logger->error("Le panier est NULL.");
		} else {
			$this->logger->info(" panier_id => Panier ID: " . $panier->getIdPanier());
		}
		// Vérification du transporteur
		if ($transporteur === null) {
			$this->logger->error("Le transporteur est NULL.");
		} else {
			$this->logger->info("transporteur_id  => Transporteur ID: " . $transporteur->getIdTransporteur());
		}
		// Vérification de la méthode de livraison
		if ($methodeLivraison === null) {
			$this->logger->error("La méthode de livraison est NULL.");
		} else {
			$this->logger->info("methode_livraison_id => Méthode de livraison ID: " . $methodeLivraison->getIdMethodeLivraison());
		}
		// Vérification de l'adresse de facturation
		if ($adresseFacturation === null) {
			$this->logger->error("L'adresse de facturation est NULL.");
		} else {
			$this->logger->info("adresse_facturation_id  => Adresse de facturation ID: " . $adresseFacturation->getIdAdresse());
		}
		// Vérification de l'adresse de livraison
		if ($adresseLivraison === null) {
			$this->logger->error("L'adresse de livraison est NULL.");
		} else {
			$this->logger->info("adresse_livraison_id => Adresse de livraison ID: " . $adresseLivraison->getIdAdresse());
		}
		// Vérification du poids
		if ($poids === null) {
			$this->logger->error("Le poids est NULL.");
			$poids = 0; // Poids par défaut
		} else {
			$this->logger->info("poids => Poids de la commande : " . $poids);
		}
		// Vérification du numéro de suivi
		if ($numeroSuivi === null) {
			$this->logger->error("Le numéro de suivi est NULL.");
			$numeroSuivi = ""; // Numéro de suivi par défaut
		} else {
			$this->logger->info("numero_suivi => Numéro de suivi : " . $numeroSuivi);
		}
		// Vérification de l'état de la commande
		if ($etatCommande === null) {
			$this->logger->error("L'état de la commande est NULL.");
			// État de la commande par défaut
			$etatCommande = $this->entityManager->getRepository(EtatCommande::class)->findOneBy(['libelle' => 'Commande Payée']);
			// Si l'état de la commande par défaut n'est pas trouvé, on le créer
			if (!$etatCommande) {
				$this->logger->error("État de la commande par défaut non trouvé.");
				$etatCommande = new EtatCommande();
				$etatCommande->setLibelle('Commande Payée');
				$this->entityManager->persist($etatCommande);
				$this->entityManager->flush();
			}
			$data->setEtatCommande($etatCommande);
		} else {
			$this->logger->info("etat_commande_id => État de la commande : " . $etatCommande->getLibelle());
		}

		// Utilisation du service pour gérer le paiement et créer la commande après validation du paiement
		try {
			// Créer la commande après paiement réussi
			$commande = $this->gestionCommandeService->creerCommandeApresPaiement(
				$panier,
				$methodeLivraison,
				$utilisateur,
				$adresseFacturation,
				$adresseLivraison,
				$numeroSuivi,
				$poids,
				$etatCommande,
			);
		} catch (\Exception $e) {
			$this->logger->error("Erreur lors du traitement de la commande : " . $e->getMessage());
			throw new BadRequestHttpException("Une erreur est survenue lors de la création de la commande.");
		}

		// Continuer avec les autres vérifications et traitements de la commande

		// Log de démarrage du processus
		$this->logger->info("Process method called for Commande ID: {$commande->getIdCommande()}");

		// Gérer les adresses similaires
		$this->verificationAdressesSimilaires($commande);

		// Assigner l'utilisateur à la commande si nécessaire
		$this->verificationUtilisateurCommande($commande, $utilisateur);

		// Générer la référence de la commande si elle n'est pas encore définie
		$this->verificationReferenceCommande($commande);

		// Transférer les informations du panier vers la commande
		if ($commande->getPanier()) {
			$this->transfertPanierVersCommande($commande->getPanier(), $commande);
		}

		// Vérifier que le prix total de la commande est correct
		$this->verifierPrixTotalCommande($commande);

		// Gérer l'historique des changements d'état de la commande
		$this->gererHistoriqueEtatCommande($commande, true);

		// Ajuster le stock des produits commandés
		$this->ajusterStockProduit($commande);

		// Clôturer le panier
		$this->cloturerPanier($commande->getPanier());

		// Persister la commande
		$this->entityManager->persist($commande);
		$this->entityManager->flush();

		// Préparation des informations pour l'email de confirmation
		$utilisateur = $commande->getUtilisateur();
		$destinataire = $utilisateur->getEmail();
		$emailData = [
			'prenom' => $utilisateur->getPrenom(),
			'orderReference' => $commande->getReference(),
			'total' => $commande->getPrixTotalCommande(),
			'products' => $commande->getCommandeProduits(), // Assure-toi que cette méthode renvoie les produits
			'deliveryAddress' => $commande->getAdresseLivraison(),
			'DeliveryPrice' => $commande->getFraisLivraison(),
		];

		// Envoi de l’email
		try {
			$this->emailService->sendEmail(
				$destinataire,
				'Félix Junot Céramique Confirmation de votre commande Référence ' . $commande->getReference(),
				'emails/order_confirmation.html.twig',
				$emailData
			);
			$this->logger->info("Email de confirmation envoyé pour la commande ID: {$commande->getIdCommande()}");
		} catch (\Exception $e) {
			$this->logger->error("Erreur lors de l'envoi de l'email de confirmation : " . $e->getMessage());
		}


		return $commande;
	}

	/**
	 * Gère la vérification et la duplication des adresses si elles sont similaires.
	 */
	private function verificationAdressesSimilaires(Commande $commande): void
	{
		$adresseLivraison = $commande->getAdresseLivraison();
		$adresseFacturation = $commande->getAdresseFacturation();

		// Vérifier si l'adresse de livraison est définie et si elle a le paramètre 'similaire' à true
		if ($adresseLivraison && $adresseLivraison->isSimilaire()) {
			// Si les adresses de facturation et livraison sont similaires
			if ($this->sontAdressesSimilaires($adresseLivraison, $adresseFacturation)) {
				$this->logger->info("Les adresses de livraison et de facturation sont similaires pour la commande : " . $commande->getReference());
			} else {
				// Sinon, cloner l'adresse de livraison pour créer une nouvelle adresse de facturation
				$adresseFacturation = clone $adresseLivraison;
				$adresseFacturation->setType('Facturation');
				$this->entityManager->persist($adresseFacturation);
				$this->entityManager->flush();

				// Assigner la nouvelle adresse de facturation à la commande
				$commande->setAdresseFacturation($adresseFacturation);

				$this->logger->info("Nouvelle adresse de facturation créée et assignée pour la commande : " . $commande->getReference());
			}
		}
	}

	/**
	 * Vérifie si deux adresses sont similaires (tous les champs doivent correspondre sauf le type et 'similaire').
	 */
	private function sontAdressesSimilaires(Adresse $adresseLivraison, Adresse $adresseFacturation): bool
	{
		// Vérifier si toutes les propriétés sont égales, sauf 'type' et 'similaire'
		return $adresseLivraison->getPrenom() === $adresseFacturation->getPrenom() &&
			$adresseLivraison->getNom() === $adresseFacturation->getNom() &&
			$adresseLivraison->getRue() === $adresseFacturation->getRue() &&
			$adresseLivraison->getBatiment() === $adresseFacturation->getBatiment() &&
			$adresseLivraison->getAppartement() === $adresseFacturation->getAppartement() &&
			$adresseLivraison->getCodePostal() === $adresseFacturation->getCodePostal() &&
			$adresseLivraison->getVille() === $adresseFacturation->getVille() &&
			$adresseLivraison->getPays() === $adresseFacturation->getPays() &&
			$adresseLivraison->getTelephone() === $adresseFacturation->getTelephone();
	}

	private function verificationUtilisateurCommande(Commande $commande, $currentUser): void
	{
		$specifiedUser = $commande->getUtilisateur();
		if ($specifiedUser === null) {
			if (!$this->security->isGranted('ROLE_ADMIN')) {
				$commande->setUtilisateur($currentUser);
			} else {
				$commande->setUtilisateur($specifiedUser ?? $currentUser);
			}
		}
	}

	private function verificationReferenceCommande(Commande $commande): void
	{
		if ($commande->getReference() === null) {
			$commande->generateReference();
		}
	}

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

		$this->logger->info("Total produits : $totalProduits, Frais livraison : $fraisLivraison, Prix attendu : $prixTotalAttendu, Prix dans la commande : " . $commande->getPrixTotalCommande());

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

	private function ajusterStockProduit(Commande $commande): void
	{
		$panier = $commande->getPanier();
		$produitsDuPanier = $panier->getPanierProduits(); // Assumes you have a relation between Panier and PanierProduit

		foreach ($produitsDuPanier as $panierProduit) {
			$produit = $panierProduit->getProduit();
			$quantiteCommandee = $panierProduit->getQuantite();

			// Ajuster le stock du produit
			$nouveauStock = $produit->getStock() - $quantiteCommandee;
			$produit->setStock($nouveauStock);

			// Log pour confirmer la mise à jour du stock
			$this->logger->info("Le stock du produit " . $produit->getNom() . " a été mis à jour. Nouveau stock : " . $nouveauStock);

			// Persister la mise à jour
			$this->entityManager->persist($produit);
		}

		// Flush les modifications du stock
		$this->entityManager->flush();
	}


	private function cloturerPanier(Panier $panier): void
	{
		// Changer l'état du panier en "FERME"
		$panier->setEtat('ferme');

		// Persister et flush l'état mis à jour
		$this->entityManager->persist($panier);
		$this->entityManager->flush();

		$this->logger->info("Panier ID " . $panier->getIdPanier() . " a été clôturé.");
	}
}
