<?php

namespace App\State;

use App\Entity\Panier;
use App\Entity\PanierProduit;
use App\Entity\Produit;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Delete as DeleteMetadata;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PanierProcessor implements ProcessorInterface
{
	private Security $security;
	private EntityManagerInterface $entityManager;
	private RequestStack $requestStack;
	private LoggerInterface $logger;

	public function __construct(Security $security, EntityManagerInterface $entityManager, RequestStack $requestStack, LoggerInterface $logger)
	{
		$this->security = $security;
		$this->entityManager = $entityManager;
		$this->requestStack = $requestStack;
		$this->logger = $logger;
	}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		$this->logger->info('PanierProcessor called.');

		// Récupérer la requête courante
		$request = $this->requestStack->getCurrentRequest();

		// Vérifier si l'utilisateur est connecté
		$utilisateur = $this->security->getUser();
		if (!$utilisateur) {
			throw new AccessDeniedHttpException('Vous devez être connecté pour ajouter ou modifier un produit dans le panier.');
		}

		// Gérer l'opération en fonction de l'URI ou de l'opération
		if ($operation->getName() === '_api_/paniers/add-product_post') {
			$this->logger->info('Handling add-product operation.');
			return $this->handleAddProduct($request, $utilisateur);
		}

		if ($operation->getName() === '_api_/paniers/{id_panier}/increment-product_patch') {
			$this->logger->info('Handling increment-product operation.');
			return $this->handleIncrementProduct($uriVariables['id_panier'], $request, $utilisateur);
		}

		if ($operation->getName() === '_api_/paniers/{id_panier}/decrement-product_patch') {
			$this->logger->info('Handling decrement-product operation.');
			return $this->handleDecrementProduct($uriVariables['id_panier'], $request, $utilisateur);
		}

		if ($operation->getName() === '_api_/paniers/payment_post') {
			$this->logger->info('Handling checkout operation.');
			return $this->createPaymentSession($utilisateur, $request);
		}

		if ($operation instanceof DeleteMetadata) {
			$this->logger->info('Handling delete-product operation.');
			return $this->handleDeleteProduct($data, $utilisateur);
		}

		throw new BadRequestHttpException('Opération non prise en charge.');
	}

	private function handleAddProduct($request, $utilisateur)
	{
		$this->logger->info('Méthode handleAddProduct appelée pour l\'utilisateur : ' . $utilisateur->getEmail());

		// Récupérer le contenu brut de la requête
		$requestContent = $request->getContent();
		$this->logger->info('Contenu brut de la requête : ' . $requestContent);

		// Décoder le contenu JSON de la requête
		$data = json_decode($requestContent, true);
		$this->logger->info('Contenu décodé de la requête : ' . print_r($data, true));

		if (!$data) {
			throw new BadRequestHttpException('Requête mal formée ou JSON invalide.');
		}

		// Vérifier si la clé "produit" existe
		if (!isset($data['produit'])) {
			throw new BadRequestHttpException('Clé "produit" manquante dans la requête.');
		}

		// Extraire l'IRI du produit
		$produitIri = $data['produit'];
		$this->logger->info('IRI du produit : ' . $produitIri);
		if (!$produitIri) {
			throw new BadRequestHttpException('IRI du produit manquant.');
		}

		// Vérification que l'IRI est correct
		$produitId = basename($produitIri);
		if (!is_numeric($produitId)) {
			throw new BadRequestHttpException('L\'IRI du produit n\'est pas valide.');
		}

		$this->logger->info('ID du produit extrait : ' . $produitId);

		// Récupérer le produit via son ID
		$produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
		if (!$produit) {
			throw new BadRequestHttpException('Produit non trouvé.');
		}

		// Extraire la quantité demandée
		$quantiteDemandee = $data['quantite'] ?? 1;
		$this->logger->info('Quantité demandée : ' . $quantiteDemandee);

		if ($quantiteDemandee < 1) {
			throw new BadRequestHttpException('La quantité doit être au moins de 1.');
		}

		// **Vérification du stock disponible**
		if ($produit->getStock() < $quantiteDemandee) {
			throw new BadRequestHttpException('Quantité demandée supérieure au stock disponible.');
		}

		// Vérifier le panier de l'utilisateur ou en créer un nouveau
		$panier = $this->entityManager->getRepository(Panier::class)->findOneBy(['utilisateur' => $utilisateur, 'etat' => 'ouvert']);
		if (!$panier) {
			$this->logger->info('Aucun panier ouvert trouvé, création d\'un nouveau panier.');
			$panier = new Panier();
			$panier->setUtilisateur($utilisateur);
			$panier->setEtat('ouvert');
			$this->entityManager->persist($panier);
			$this->entityManager->flush();  // Flush pour s'assurer que le panier est bien créé avant d'ajouter le produit
		}

		// Vérifier si le produit est déjà dans le panier
		$panierProduit = $this->entityManager->getRepository(PanierProduit::class)->findOneBy(['panier' => $panier, 'produit' => $produit]);
		if (!$panierProduit) {
			$this->logger->info('Ajout du produit au panier.');
			// Si le produit n'est pas encore dans le panier, créer une nouvelle ligne
			$panierProduit = new PanierProduit();
			$panierProduit->setProduit($produit);
			$panierProduit->setPanier($panier);
			$panierProduit->setQuantite($quantiteDemandee);
			$this->entityManager->persist($panierProduit);
		} else {
			// Si le produit est déjà dans le panier, mettre à jour la quantité
			$quantiteTotale = $panierProduit->getQuantite() + $quantiteDemandee;

			// **Vérification du stock total après mise à jour de la quantité**
			if ($produit->getStock() < $quantiteTotale) {
				throw new BadRequestHttpException('Quantité totale dans le panier supérieure au stock disponible.');
			}

			$this->logger->info('Mise à jour de la quantité : ' . $quantiteTotale);
			$panierProduit->setQuantite($quantiteTotale);
		}

		// Recalculer le prix total du produit dans le panier
		$panierProduit->recalculatePrixTotalProduit();

		// Sauvegarder les modifications pour PanierProduit
		$this->entityManager->flush();  // Flush pour sauvegarder le produit dans le panier

		// ** Rafraîchir le panier pour s'assurer que la collection de produits est mise à jour **
		$this->entityManager->refresh($panier);

		// Recalculer le prix total du panier après ajout/modification du produit
		$prixTotalPanier = '0.00';

		$panierProduits = $panier->getPanierProduits();
		$this->logger->info('Produits dans le panier après refresh : ' . count($panierProduits));

		// Log chaque produit pour vérifier qu'ils sont bien présents
		foreach ($panierProduits as $produitDansPanier) {
			$this->logger->info('Produit dans panier: ' . $produitDansPanier->getProduit()->getNom() . ' - Quantité: ' . $produitDansPanier->getQuantite() . ' - Prix total produit: ' . $produitDansPanier->getPrixTotalProduit());
			$prixTotalPanier = bcadd($prixTotalPanier, $produitDansPanier->getPrixTotalProduit(), 2);
		}

		$panier->setPrixTotalPanier($prixTotalPanier);
		$this->logger->info('Prix total du panier mis à jour : ' . $prixTotalPanier);

		// Enregistrer les modifications finales
		$this->entityManager->flush();

		return $panier;
	}

	private function handleIncrementProduct($idPanier, $request, $utilisateur)
	{
		$this->logger->info('Méthode handleIncrementProduct appelée pour le panier ID : ' . $idPanier);

		// Récupérer le panier
		$panier = $this->entityManager->getRepository(Panier::class)->find($idPanier);
		if (!$panier || $panier->getUtilisateur() !== $utilisateur) {
			throw new AccessDeniedHttpException('Accès refusé.');
		}

		// Récupérer le contenu brut de la requête
		$requestContent = $request->getContent();
		$this->logger->info('Contenu brut de la requête : ' . $requestContent);

		// Décoder le contenu JSON de la requête
		$data = json_decode($requestContent, true);
		$this->logger->info('Contenu décodé de la requête : ' . print_r($data, true));

		if (!$data) {
			throw new BadRequestHttpException('Requête mal formée ou JSON invalide.');
		}

		// Vérifier si la clé "produit" existe
		if (!isset($data['produit'])) {
			throw new BadRequestHttpException('Clé "produit" manquante dans la requête.');
		}

		// Extraire l'IRI du produit
		$produitIri = $data['produit'];
		$this->logger->info('IRI du produit : ' . $produitIri);

		// Vérification que l'IRI est correct
		$produitId = basename($produitIri);
		if (!is_numeric($produitId)) {
			throw new BadRequestHttpException('L\'IRI du produit n\'est pas valide.');
		}

		// Récupérer le produit
		$produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
		if (!$produit) {
			throw new BadRequestHttpException('Produit non trouvé.');
		}

		// Récupérer le produit du panier
		$panierProduit = $this->entityManager->getRepository(PanierProduit::class)->findOneBy(['panier' => $panier, 'produit' => $produit]);
		if (!$panierProduit) {
			throw new BadRequestHttpException('Le produit n\'est pas dans le panier.');
		}

		// Incrémenter la quantité
		$newQuantite = $panierProduit->getQuantite() + 1;
		$this->logger->info('Nouvelle quantité après incrémentation : ' . $newQuantite);

		// Vérifier le stock disponible
		if ($newQuantite > $produit->getStock()) {
			throw new BadRequestHttpException('Stock insuffisant.');
		}

		// Mettre à jour la quantité
		$panierProduit->setQuantite($newQuantite);
		$panierProduit->recalculatePrixTotalProduit();  // Recalculer le prix total du produit

		// Recalculer le prix total du panier
		$prixTotalPanier = '0.00';
		foreach ($panier->getPanierProduits() as $produitDansPanier) {
			$prixTotalPanier = bcadd($prixTotalPanier, $produitDansPanier->getPrixTotalProduit(), 2);
		}
		$panier->setPrixTotalPanier($prixTotalPanier);
		$this->logger->info('Prix total du panier mis à jour : ' . $prixTotalPanier);

		// Enregistrer les modifications
		$this->entityManager->flush();

		return $panier;
	}

	private function handleDecrementProduct($idPanier, $request, $utilisateur)
	{
		$this->logger->info('Méthode handleDecrementProduct appelée pour le panier ID : ' . $idPanier);

		// Récupérer le panier
		$panier = $this->entityManager->getRepository(Panier::class)->find($idPanier);
		if (!$panier || $panier->getUtilisateur() !== $utilisateur) {
			throw new AccessDeniedHttpException('Accès refusé.');
		}

		// Récupérer le contenu brut de la requête
		$requestContent = $request->getContent();
		$this->logger->info('Contenu brut de la requête : ' . $requestContent);

		// Décoder le contenu JSON de la requête
		$data = json_decode($requestContent, true);
		$this->logger->info('Contenu décodé de la requête : ' . print_r($data, true));

		if (!$data) {
			throw new BadRequestHttpException('Requête mal formée ou JSON invalide.');
		}

		// Vérifier si la clé "produit" existe
		if (!isset($data['produit'])) {
			throw new BadRequestHttpException('Clé "produit" manquante dans la requête.');
		}

		// Extraire l'IRI du produit
		$produitIri = $data['produit'];
		$this->logger->info('IRI du produit : ' . $produitIri);

		// Vérification que l'IRI est correct
		$produitId = basename($produitIri);
		if (!is_numeric($produitId)) {
			throw new BadRequestHttpException('L\'IRI du produit n\'est pas valide.');
		}

		// Récupérer le produit
		$produit = $this->entityManager->getRepository(Produit::class)->find($produitId);
		if (!$produit) {
			throw new BadRequestHttpException('Produit non trouvé.');
		}

		// Récupérer le produit du panier
		$panierProduit = $this->entityManager->getRepository(PanierProduit::class)->findOneBy(['panier' => $panier, 'produit' => $produit]);
		if (!$panierProduit) {
			throw new BadRequestHttpException('Le produit n\'est pas dans le panier.');
		}

		// Décrémenter la quantité
		$newQuantite = $panierProduit->getQuantite() - 1;
		$this->logger->info('Nouvelle quantité après décrémentation : ' . $newQuantite);

		if ($newQuantite < 1) {
			// Si la quantité devient inférieure à 1, retirer le produit du panier
			$this->entityManager->remove($panierProduit);
			$this->logger->info('Produit retiré du panier.');

			// Enregistrer la suppression immédiatement
			$this->entityManager->flush();
		} else {
			// Mettre à jour la quantité
			$panierProduit->setQuantite($newQuantite);
			$panierProduit->recalculatePrixTotalProduit();  // Recalculer le prix total du produit
			$this->logger->info('Quantité mise à jour dans le panier : ' . $newQuantite);
		}

		// Recalculer le prix total du panier
		$prixTotalPanier = '0.00';
		foreach ($panier->getPanierProduits() as $produitDansPanier) {
			$prixTotalPanier = bcadd($prixTotalPanier, $produitDansPanier->getPrixTotalProduit(), 2);
		}

		$compte = (count($panier->getPanierProduits()));
		$this->logger->info('compte : ' . $compte);
		// Si tous les produits ont été retirés, le prix total du panier doit être 0
		if (count($panier->getPanierProduits()) === 0) {
			$prixTotalPanier = '0.00';
			$this->logger->info('Aucun produit dans le panier, prix total du panier mis à jour à 0.');
		}

		$panier->setPrixTotalPanier($prixTotalPanier);
		$this->logger->info('Prix total du panier mis à jour : ' . $prixTotalPanier);

		// Enregistrer les modifications
		$this->entityManager->flush();

		return $panier;
	}

	private function handleDeleteProduct(PanierProduit $panierProduit, $utilisateur)
	{
		// Rechercher le panier
		$panier = $panierProduit->getPanier();
		$this->logger->info('Suppression d\'un produit du panier. ID Panier : ' . $panier->getIdPanier());

		// Supprimer le produit du panier
		$this->entityManager->remove($panierProduit);
		$this->entityManager->flush(); // Assure-toi que la suppression est enregistrée avant de recalculer

		// Rafraîchir le panier pour obtenir les produits mis à jour
		$this->entityManager->refresh($panier);

		// Recalculer le prix total du panier après la suppression
		$prixTotalPanier = '0.00';
		foreach ($panier->getPanierProduits() as $produitDansPanier) {
			$this->logger->info('Produit restant dans le panier : ' . $produitDansPanier->getProduit()->getNom() . ', Quantité : ' . $produitDansPanier->getQuantite() . ', Prix total produit : ' . $produitDansPanier->getPrixTotalProduit());
			$prixTotalPanier = bcadd($prixTotalPanier, $produitDansPanier->getPrixTotalProduit(), 2);
		}

		// Vérifier si le panier est vide après la suppression
		if (count($panier->getPanierProduits()) === 0) {
			$prixTotalPanier = '0.00';
			$this->logger->info('Aucun produit dans le panier, prix total du panier mis à jour à 0.');
		}

		$panier->setPrixTotalPanier($prixTotalPanier);
		$this->logger->info('Prix total du panier mis à jour après suppression : ' . $prixTotalPanier);

		// Enregistrer les modifications
		$this->entityManager->flush();

		return $panier;
	}

	private function createPaymentSession(Utilisateur $user, Request $request): JsonResponse
	{
		try {
			Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

			// Vérifier la clé API Stripe
			$this->logger->info('Clé API Stripe : ' . $_ENV['STRIPE_SECRET_KEY']);

			// Récupérer le contenu brut de la requête pour débogage
			$requestContent = $request->getContent();
			$this->logger->info('Contenu brut de la requête : ' . $requestContent);

			// Extraire l'ID du panier depuis les données de la requête
			$data = json_decode($requestContent, true);
			// Récupérer l'ID du panier
			$panierId = $data['id_panier'] ?? null;
			// Récupérer les frais de livraison de la requête du front
			$fraisLivraison = $data['fraisLivraison'] ?? 0;

			// Récupérer le panier en vérifiant qu'il appartient à l'utilisateur
			$panier = $this->entityManager->getRepository(Panier::class)->find($panierId);
			if (!$panier || $panier->getUtilisateur()->getIdUtilisateur() !== $user->getIdUtilisateur()) {
				$this->logger->error('Panier non trouvé ou ne correspond pas à l\'utilisateur.');
				return new JsonResponse(['error' => 'Panier introuvable ou accès refusé.'], 404);
			}

			// Vérifier que le panier contient des produits
			$panierProduits = $panier->getPanierProduits();
			if (empty($panierProduits)) {
				$this->logger->error('Le panier est vide. Aucun produit à payer.');
				return new JsonResponse(['error' => 'Aucun produit dans le panier.'], 400);
			}

			// Préparer les articles pour la session de paiement
			$lineItems = [];
			foreach ($panierProduits as $panierProduit) {
				$produit = $panierProduit->getProduit();
				if (!$produit || !$produit->getNom() || !$produit->getPrixTtc()) {
					$this->logger->error('Erreur dans les informations du produit : ' . json_encode($produit));
					return new JsonResponse(['error' => 'Erreur dans les informations d\'un produit.'], 400);
				}
				$lineItems[] = [
					'price_data' => [
						'currency' => 'eur',
						'product_data' => [
							'name' => $produit->getNom(),
						],
						'unit_amount' => intval($produit->getPrixTtc() * 100),
					],
					'quantity' => $panierProduit->getQuantite(),
				];
			}

			// Ajouter les frais de livraison comme item supplémentaire
			if ($fraisLivraison > 0) {
				$lineItems[] = [
					'price_data' => [
						'currency' => 'eur',
						'product_data' => [
							'name' => 'Frais de livraison',
						],
						'unit_amount' => intval($fraisLivraison * 100), // Conversion en centimes
					],
					'quantity' => 1,
				];
			}

			$this->logger->info('Contenu des lineItems pour Stripe : ' . print_r($lineItems, true));

			// Créer la session Stripe Checkout
			$session = Session::create([
				'payment_method_types' => ['card'],
				'line_items' => $lineItems,
				'mode' => 'payment',
				'success_url' => $_ENV['FRONTEND_URL'] . '/order-success?session_id={CHECKOUT_SESSION_ID}',
				'cancel_url' => $_ENV['FRONTEND_URL'] . '/order-cancel',
				'client_reference_id' => (string) $user->getIdUtilisateur(),
			]);

			$this->logger->info('Session de paiement Stripe créée avec succès : ' . $session->id);

			// Retourner l'ID de la session
			return new JsonResponse(['id' => $session->id]);
		} catch (\Exception $e) {
			$this->logger->error('Erreur lors de la création de la session de paiement : ' . $e->getMessage());
			return new JsonResponse(['error' => 'Erreur lors de la création de la session de paiement.'], 500);
		}
	}
}
