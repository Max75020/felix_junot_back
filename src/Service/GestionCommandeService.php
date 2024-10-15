<?php

namespace App\Service;

use App\Entity\Adresse;
use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\MethodeLivraison;
use App\Entity\EtatCommande;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Service\StripeService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GestionCommandeService
{
	private EntityManagerInterface $entityManager;
	private StripeService $stripeService;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $entityManager, StripeService $stripeService, LoggerInterface $logger)
	{
		$this->entityManager = $entityManager;
		$this->stripeService = $stripeService;
		$this->logger = $logger;
	}

	public function creerCommandeApresPaiement(
		Panier $panier,
		MethodeLivraison $methodeLivraison,
		$utilisateur,
		Adresse $adresseFacturation,
		Adresse $adresseLivraison,
		string $numeroSuivi,
		string $poids,
		EtatCommande $etatCommande
	): Commande {
		// Afficher un message pour indiquer le début de la création de la commande en console
		//echo "Création de la commande après paiement\n";
		// Afficher un message pour indiquer le début de la création de la commande en log
		$this->logger->info("Création de la commande après paiement");

		// 1. Récupérer et vérifier le transporteur (qui doit être en lien avec la méthode de livraison)
		$transporteur = $methodeLivraison->getTransporteur();
		if ($transporteur === null) {
			echo "Le transporteur est NULL\n";
			throw new \InvalidArgumentException("Le transporteur est requis pour la méthode de livraison.");
		}
		// Afficher l'ID du transporteur en console
		//echo "Transporteur ID: " . $transporteur->getIdTransporteur() . "\n";
		// Afficher l'ID du transporteur en log
		$this->logger->info("Transporteur ID: " . $transporteur->getIdTransporteur());

		// 2. Calculer le montant total du panier
		$prixTotalPanier = $this->calculerTotalPanier($panier);
		// Afficher le prix total du panier en console
		//echo "Prix total du panier : $prixTotalPanier\n";
		// Afficher le prix total du panier en log
		$this->logger->info("Prix total du panier : $prixTotalPanier");


		// 3. Calculer les frais de livraison
		$fraisLivraison = $this->calculerFraisLivraison($methodeLivraison);
		// Afficher les frais de livraison en console
		//echo "Frais de livraison : $fraisLivraison\n";
		// Afficher les frais de livraison en log
		$this->logger->info("Frais de livraison : $fraisLivraison");

		// 4. Calculer le montant total (produits + livraison)
		$montantTotal = bcadd($prixTotalPanier, $fraisLivraison, 2); // Total en euros
		// Afficher le montant total en console
		//echo "Montant total (produits + livraison) : $montantTotal euros\n";
		// Afficher le montant total en log
		$this->logger->info("Montant total (produits + livraison) : $montantTotal euros");

		// 5. Valider le paiement avec Stripe
		$montantTotalStripe = bcmul($montantTotal, '100', 0); // Convertir en centimes
		// Afficher le montant total pour Stripe en console
		//echo "Montant total pour Stripe (en centimes) : $montantTotalStripe\n";
		// Afficher le montant total pour Stripe en log
		$this->logger->info("Montant total pour Stripe (en centimes) : $montantTotalStripe");

		if (!$this->validerPaiementAvecStripe($montantTotalStripe)) {
			// Afficher un message d'erreur si le paiement n'est pas validé en console
			//echo "Échec de la validation du paiement avec Stripe\n";
			// Afficher un message d'erreur si le paiement n'est pas validé en log
			$this->logger->error("Échec du paiement, la commande ne peut pas être créée.");
			throw new BadRequestHttpException("Échec du paiement, la commande ne peut pas être créée.");
		}

		// Afficher un message de succès si le paiement est validé en console
		//echo "Paiement validé\n";
		// Afficher un message de succès si le paiement est validé en log
		$this->logger->info("Paiement validé");

		// 6. Mettre à jour l'état de la commande
		$etatCommande = $this->entityManager->getRepository(EtatCommande::class)->findOneBy(['libelle' => 'Commande Payée']);
		if (!$etatCommande) {
			$etatCommande = new EtatCommande();
			$etatCommande->setLibelle('Commande Payée');
			$this->entityManager->persist($etatCommande);
			$this->entityManager->flush();
		}
		// Afficher l'état de la commande en console
		//echo "État de la commande : " . $etatCommande->getLibelle() . "\n";
		// Afficher l'état de la commande en log
		$this->logger->info("État de la commande : " . $etatCommande->getLibelle());

		// 7. Créer la commande avec tous les détails supplémentaires
		return $this->creerCommande(
			$panier,
			$methodeLivraison,
			$utilisateur,
			$prixTotalPanier,
			$fraisLivraison,
			$montantTotal,
			$adresseFacturation,
			$adresseLivraison,
			$numeroSuivi,
			$poids,
			$etatCommande
		);
	}

	private function validerPaiementAvecStripe(int $montantTotalStripe): bool
	{
		try {
			// Envoyer le montant déjà converti en centimes
			$paymentIntent = $this->stripeService->createPaymentIntent($montantTotalStripe);
			return $paymentIntent->status === 'succeeded';
		} catch (\Exception $e) {
			// Log plus détaillé de l'erreur Stripe
			$this->logger->error('Erreur lors du paiement avec Stripe : ' . $e->getMessage());
			$this->logger->error('Détails de l\'exception : ' . $e->getTraceAsString());
			echo "Erreur Stripe : " . $e->getMessage() . "\n";
			return false;
		}
	}

	/**
	 * Crée une nouvelle commande après validation du paiement.
	 *
	 * @param Panier $panier L'objet Panier contenant les produits sélectionnés par l'utilisateur.
	 * @param MethodeLivraison $methodeLivraison La méthode de livraison sélectionnée par l'utilisateur.
	 * @param Utilisateur $utilisateur L'utilisateur qui passe la commande.
	 * @param string $prixTotalPanier Le total du prix des produits dans le panier sous forme de chaîne.
	 * @param string $fraisLivraison Les frais de livraison appliqués à la commande sous forme de chaîne.
	 * @param string $montantTotal Le montant total de la commande (produits + livraison) sous forme de chaîne.
	 * @param Adresse $adresseFacturation L'adresse de facturation de la commande.
	 * @param Adresse $adresseLivraison L'adresse de livraison de la commande.
	 * @param string $numeroSuivi Le numéro de suivi généré ou fourni pour la commande.
	 * @param string|null $poids Le poids total de la commande sous forme de chaîne, peut être null si non applicable.
	 * @param EtatCommande $etatCommande L'état initial de la commande (ex. : "en attente", "confirmée", etc.).
	 *
	 * @return Commande La commande nouvellement créée.
	 */
	private function creerCommande(
		Panier $panier,
		MethodeLivraison $methodeLivraison,
		$utilisateur,
		string $prixTotalPanier,
		string $fraisLivraison,
		string $montantTotal,
		Adresse $adresseFacturation,
		Adresse $adresseLivraison,
		string $numeroSuivi,
		?string $poids,
		EtatCommande $etatCommande
	): Commande {
		// Afficher un message pour indiquer le début de la création de la commande en console
		//echo "Début de la méthode creerCommande\n";
		// Afficher un message pour indiquer le début de la création de la commande en log
		$this->logger->info("Début de la méthode creerCommande");

		// Créer une nouvelle instance de la classe Commande
		$commande = new Commande();

		// Afficher un message pour indiquer la création de la commande en console
		//echo "Ajout de l'utilisateur à la commande : utilisateur_id " . ($utilisateur ? $utilisateur->getIdUtilisateur() : 'NULL') . "\n";
		// Afficher un message pour indiquer la création de la commande en log
		$this->logger->info("Ajout de l'utilisateur à la commande : utilisateur_id " . ($utilisateur ? $utilisateur->getIdUtilisateur() : 'NULL'));
		// Associer l'utilisateur à la commande (celui qui passe la commande)
		$commande->setUtilisateur($utilisateur);

		// Généré une référence unique pour la commande à l'aide de la méthode generateReference basé sur l'id utilisateur et la date
		$commande->generateReference();

		// Afficher un message pour indiquer l'ajout du panier à la commande en console
		//echo "Ajout du panier à la commande : panier_id " . ($panier ? $panier->getIdPanier() : 'NULL') . "\n";
		// Afficher un message pour indiquer l'ajout du panier à la commande en log
		$this->logger->info("Ajout du panier à la commande : panier_id " . ($panier ? $panier->getIdPanier() : 'NULL'));
		// Associer le panier à la commande
		$commande->setPanier($panier);

		// Associer le transporteur à la commande (lié à la méthode de livraison)
		$transporteur = $methodeLivraison->getTransporteur();
		// Afficher un message pour indiquer l'ajout du transporteur à la commande en console
		//echo "Ajout du transporteur à la commande : transporteur_id " . ($transporteur ? $transporteur->getIdTransporteur() : 'NULL') . "\n";
		// Afficher un message pour indiquer l'ajout du transporteur à la commande en log
		$this->logger->info("Ajout du transporteur à la commande : transporteur_id " . ($transporteur ? $transporteur->getIdTransporteur() : 'NULL'));
		$commande->setTransporteur($transporteur);

		// Afficher un message pour indiquer l'ajout de la méthode de livraison à la commande en console
		//echo "Ajout de la méthode de livraison : methode_livraison_id " . ($methodeLivraison ? $methodeLivraison->getIdMethodeLivraison() : 'NULL') . "\n";
		// Afficher un message pour indiquer l'ajout de la méthode de livraison à la commande en log
		$this->logger->info("Ajout de la méthode de livraison : methode_livraison_id " . ($methodeLivraison ? $methodeLivraison->getIdMethodeLivraison() : 'NULL'));
		// Associer la méthode de livraison à la commande
		$commande->setMethodeLivraison($methodeLivraison);

		// Afficher un message pour indiquer l'ajout du total des produits à la commande en console
		//echo "Ajout du total des produits à la commande : total_produits_commande " . $prixTotalPanier . "\n";
		// Afficher un message pour indiquer l'ajout du total des produits à la commande en log
		$this->logger->info("Ajout du total des produits à la commande : total_produits_commande " . $prixTotalPanier);
		// Ajouter le prix total des produits à la commande (chaîne de caractères)
		$commande->setTotalProduitsCommande($prixTotalPanier);

		// Afficher un message pour indiquer l'ajout des frais de livraison à la commande en console
		//echo "Ajout des frais de livraison à la commande : frais_livraison " . $fraisLivraison . "\n";
		// Afficher un message pour indiquer l'ajout des frais de livraison à la commande en log
		$this->logger->info("Ajout des frais de livraison à la commande : frais_livraison " . $fraisLivraison);
		// Ajouter les frais de livraison à la commande (chaîne de caractères)
		$commande->setFraisLivraison($fraisLivraison);

		// Afficher un message pour indiquer l'ajout du montant total à la commande en console
		//echo "Ajout du montant total à la commande : prix_total_commande " . $montantTotal . "\n";
		// Afficher un message pour indiquer l'ajout du montant total à la commande en log
		$this->logger->info("Ajout du montant total à la commande : prix_total_commande " . $montantTotal);
		// Ajouter le montant total (produits + livraison) à la commande (chaîne de caractères)
		$commande->setPrixTotalCommande($montantTotal);

		// Afficher un message pour indiquer l'ajout de l'adresse de facturation à la commande en console
		//echo "Ajout de l'adresse de facturation ID : adresse_facturation_id " . $adresseFacturation->getIdAdresse() . "\n";
		// Afficher un message pour indiquer l'ajout de l'adresse de facturation à la commande en log
		$this->logger->info("Ajout de l'adresse de facturation ID : adresse_facturation_id " . $adresseFacturation->getIdAdresse());
		// Ajouter l'adresse de facturation à la commande
		$commande->setAdresseFacturation($adresseFacturation);

		// Afficher un message pour indiquer l'ajout de l'adresse de livraison à la commande en console
		//echo "Ajout de l'adresse de livraison ID : adresse_livraison_id " . $adresseLivraison->getIdAdresse() . "\n";
		// Afficher un message pour indiquer l'ajout de l'adresse de livraison à la commande en log
		$this->logger->info("Ajout de l'adresse de livraison ID : adresse_livraison_id " . $adresseLivraison->getIdAdresse());
		// Ajouter l'adresse de livraison à la commande
		$commande->setAdresseLivraison($adresseLivraison);

		// Afficher un message pour indiquer l'ajout du numéro de suivi à la commande en console
		//echo "Ajout du numéro de suivi : numero_suivi " . $numeroSuivi . "\n";
		// Afficher un message pour indiquer l'ajout du numéro de suivi à la commande en log
		$this->logger->info("Ajout du numéro de suivi : numero_suivi " . $numeroSuivi);
		// Ajouter le numéro de suivi à la commande (chaîne de caractères)
		$commande->setNumeroSuivi($numeroSuivi);

		// Ajouter le poids total de la commande (si applicable, chaîne de caractères)
		if ($poids !== null) {
			// Afficher un message pour indiquer l'ajout du poids total de la commande en console
			//echo "Ajout du poids total de la commande :  poids" . $poids . "\n";
			// Afficher un message pour indiquer l'ajout du poids total de la commande en log
			$this->logger->info("Ajout du poids total de la commande :  poids" . $poids);
			$commande->setPoids($poids);
		}

		// Vérifier si l'état de la commande est défini
		if ($etatCommande === null) {
			throw new \InvalidArgumentException("L'état de la commande est obligatoire.");
		}

		// Afficher un message pour indiquer l'ajout de l'état de la commande en console
		//echo "Ajout de l'état de la commande : etat_commande_id " . $etatCommande->getIdEtatCommande() . "\n";
		// Afficher un message pour indiquer l'ajout de l'état de la commande en log
		$this->logger->info("Ajout de l'état de la commande : etat_commande_id " . $etatCommande->getIdEtatCommande());
		// Associer l'état initial de la commande (exemple : "En attente")
		$commande->setEtatCommande($etatCommande);

		// Afficher un message pour indiquer le persist de la commande dans la base de données en console
		//echo "Persist de la commande dans la base de données\n";
		// Afficher un message pour indiquer le persist de la commande dans la base de données en log
		$this->logger->info("Persist de la commande dans la base de données");
		// Persist de la commande dans la base de données
		$this->entityManager->persist($commande);

		// Afficher un message pour indiquer le flush de la commande dans la base de données en console
		//echo "Flush de la commande dans la base de données\n";
		// Afficher un message pour indiquer le flush de la commande dans la base de données en log
		$this->logger->info("Flush de la commande dans la base de données");
		// Envoie des changements à la base de données
		$this->entityManager->flush();

		// Afficher un message de succès une fois la commande créée en console
		//echo "Commande créée avec succès : ID " . $commande->getIdCommande() . "\n";
		// Afficher un message de succès une fois la commande créée en log
		$this->logger->info("Commande créée avec succès : ID " . $commande->getIdCommande());

		// Retourner la commande nouvellement créée
		return $commande;
	}

	private function calculerTotalPanier(Panier $panier): string
	{
		return $panier->getPrixTotalPanier();
	}

	private function calculerFraisLivraison(MethodeLivraison $methodeLivraison): string
	{
		return $methodeLivraison->getPrix();
	}
}
