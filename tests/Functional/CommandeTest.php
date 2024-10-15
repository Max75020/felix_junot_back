<?php

namespace App\Tests\Functional;

use App\Tests\Authentificator\TestAuthentificator;
use Symfony\Component\HttpFoundation\Response;

class CommandeTest extends TestAuthentificator
{

	public function createAdminClient()
	{
		return $this->createAuthenticatedClient(true);
	}

	public function createUserClient()
	{
		return $this->createAuthenticatedClient();
	}

	/**
	 * Test que l'utilisateur peut récupérer sa collection de commandes.
	 */
	public function testUserCanGetCommandeCollection()
	{
		// Créer un utilisateur A unique
		$userA = $this->createUniqueUser();
		$userAClient = $userA['client'];
		$userAIri = $userA['iri'];
		$userAId = $userA['id'];

		// Créer deux commandes pour l'utilisateur A
		$commandeA1 = $this->createCommandeTest($userAClient);
		$commandeA2 = $this->createCommandeTest($userAClient);

		// Créer un utilisateur B unique
		$userB = $this->createUniqueUser();
		$userBClient = $userB['client'];
		$userBIri = $userB['iri'];
		$userBId = $userB['id'];

		// Créer une commande pour l'utilisateur B
		$commandeB1 = $this->createCommandeTest($userBClient);

		// Vérifier que les utilisateurs et les commandes sont différents
		$this->assertNotEmpty($userA['iri'], 'IRI de l\'utilisateur A est vide.');
		$this->assertNotEmpty($userB['iri'], 'IRI de l\'utilisateur B est vide.');

		$this->assertNotEmpty($userA['id'], 'ID de l\'utilisateur A est vide.');
		$this->assertNotEmpty($userB['id'], 'ID de l\'utilisateur B est vide.');

		$this->assertNotEquals($userA['id'], $userB['id'], 'Les utilisateurs A et B ont le même ID.');
		$this->assertNotEquals($userA['iri'], $userB['iri'], 'Les utilisateurs A et B ont le même IRI.');

		$this->assertNotEquals($userA['iri'], $userB['iri'], 'Les utilisateurs A et B ont le même IRI.');
		$this->assertNotEquals($userA['email'], $userB['email'], 'Les utilisateurs A et B ont le même email.');


		// L'utilisateur A récupère la collection de commandes
		$responseGetCollection = $userAClient->request('GET', '/api/commandes', [], [], [
			'HTTP_ACCEPT' => 'application/json',
		]);

		// Vérifier que la réponse est OK
		$this->assertEquals(Response::HTTP_OK, $responseGetCollection->getStatusCode(), 'L\'utilisateur standard ne peut pas récupérer toutes ses commandes.');

		// Récupérer les données de la réponse
		$responseData = $userAClient->getResponse()->toArray();

		// Vérifier que seules les commandes de l'utilisateur A sont présentes
		foreach ($responseData['hydra:member'] as $commande) {
			$this->assertEquals(
				$userAIri,
				$commande['utilisateur'],
				'La commande ne appartient pas à l\'utilisateur connecté.'
			);
		}

		// Vérifier le nombre de commandes retournées (2 commandes pour l'utilisateur A)
		$this->assertCount(
			2,
			$responseData['hydra:member'],
			'L\'utilisateur voit des commandes qui ne lui appartiennent pas ou pas le nombre attendu.'
		);
	}


	public function testAdminCanPerformCrudOperations()
	{
		$client = $this->createAdminClient();
		$iri = $this->createCommandeTest();

		// GET specific
		$responseGet = $client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_OK, $responseGet->getStatusCode(), 'L\'administrateur ne peut pas récupérer une commande spécifique.');

		// GET collection
		$responseGetCollection = $client->request('GET', '/api/commandes');
		$this->assertEquals(Response::HTTP_OK, $responseGetCollection->getStatusCode(), 'L\'administrateur ne peut pas récupérer toutes les commandes.');

		// PATCH
		$responsePatch = $client->request('PATCH', $iri, [
			'json' => [
				"total" => "10.10"
			],
			'headers' => ['Content-Type' => 'application/merge-patch+json']
		]);
		// Vérifier le statut de la réponse
		$this->assertEquals(Response::HTTP_OK, $responsePatch->getStatusCode(), 'L\'administrateur ne peut pas mettre à jour une commande.');

		// DELETE
		$responseDelete = $client->request('DELETE', $iri);
		// Vérifier le statut de la réponse
		$this->assertEquals(Response::HTTP_NO_CONTENT, $responseDelete->getStatusCode(), 'L\'administrateur ne peut pas supprimer une commande.');
	}

	public function testCompleteOrderProcess()
	{
		// 1. Créer un client authentifié pour l'utilisateur
		$client = $this->createAuthenticatedClient();
		echo "\n--- 1. Créer un client authentifié pour l'utilisateur ---\n";

		// 2. Créer un produit
		$produitIri = $this->createProduit();
		echo "\n--- 2. Créer un produit ---\n";

		// 3. Ajouter le produit au panier avec une quantité de 2
		$responseAddToPanier = $client->request('POST', '/api/paniers/add-product', [
			'json' => [
				'produit' => $produitIri,
				'quantite' => 2
			]
		]);

		$this->assertResponseIsSuccessful();
		echo "\n--- 3. Ajouter le produit au panier avec une quantité de 2 ---\n";

		// 4. Récupérer l'IRI du panier créé
		$panierData = $responseAddToPanier->toArray();
		$panierIri = $panierData['@id'];
		echo "\n--- 4. Récupérer l'IRI du panier : $panierIri ---\n";

		// 5. Vérifier que le produit a bien été ajouté au panier avec la bonne quantité
		$responsePanierVerification = $client->request('GET', $panierIri);
		$panierDetails = $responsePanierVerification->toArray();
		$this->assertCount(1, $panierDetails['panierProduits'], 'Le produit n\'a pas été ajouté correctement au panier.');
		echo "\n--- 5. Vérification du panier ---\n";

		// 6. Créer une adresse pour l'utilisateur
		$utilisateurIri = $this->getUserIri($client);
		$adresseIri = $this->createAdresse($client, $utilisateurIri);
		echo "\n--- 6. Créer une adresse pour la commande ---\n";

		// 7. Créer un transporteur
		$transporteurIri = $this->createTransporteur();
		echo "\n--- 7. Créer un transporteur ---\n";

		// 8. Créer une méthode de livraison
		$methodeLivraisonIri = $this->createMethodeLivraison($transporteurIri);
		echo "\n--- 8. Créer une méthode de livraison ---\n";

		// 9. Simuler le paiement avec Stripe avant la création de la commande
		echo "\n--- 9. Simuler le paiement de la commande avec Stripe ---\n";

		// Récupérer le montant total du panier et le prix de la livraison
		$prixTotalPanier = $panierDetails['prix_total_panier'];
		$responseLivraison = $client->request('GET', $methodeLivraisonIri);
		$livraisonData = $responseLivraison->toArray();
		$prixLivraison = $livraisonData['prix'];
		$montantTotal = round(bcadd($prixTotalPanier, $prixLivraison, 2), 2); // Montant en euros

		// Simuler la validation du paiement avec Stripe
		$montantTotalStripe = bcmul($montantTotal, '100', 0); // Convertir en centimes pour Stripe
		$stripeClient = $this->createStripeClient();
		$paymentIntent = $stripeClient->paymentIntents->create([
			'amount' => $montantTotalStripe,
			'currency' => 'eur',
			'payment_method_types' => ['card'],
			'payment_method' => 'pm_card_visa',
			'confirm' => true
		]);

		// Vérifier que le paiement a été accepté
		$this->assertEquals('succeeded', $paymentIntent->status, 'Le paiement n\'a pas été validé.');
		echo "\n--- 9. Paiement réussi, création de la commande ---\n";

		echo "\n--- Paiement réussi, création de la commande ---\n";
		echo "\n--- Montant du panier : $prixTotalPanier € ---\n";
		echo "\n--- Frais de livraison : $prixLivraison € ---\n";
		echo "\n--- Montant total payé : $montantTotal € ---\n";

		print_r([
			'utilisateur' => $utilisateurIri,
			'etat_commande' => $this->createEtatCommandeTest(),
			'prix_total_commande' => $montantTotal,
			'poids' => '3.5',
			'frais_livraison' => $prixLivraison,
			'numero_suivi' => 'ABC12345678',
			'adresseLivraison' => $adresseIri,
			'adresseFacturation' => $adresseIri,
			'methodeLivraison' => $methodeLivraisonIri,
			'panier' => $panierIri,
			'trabsporteur' => $transporteurIri,
			'total_produits_commande' => $prixTotalPanier
		]);

		// 10. Créer la commande après validation du paiement
		$responseCommande = $client->request('POST', '/api/commandes', [
			'json' => [
				'utilisateur' => $utilisateurIri,
				'etat_commande' => $this->createEtatCommandeTest(),
				'prix_total_commande' => $montantTotal,
				'poids' => '3.5',
				'frais_livraison' => $prixLivraison,
				'numero_suivi' => 'ABC12345678',
				'adresseLivraison' => $adresseIri,
				'adresseFacturation' => $adresseIri,
				'methodeLivraison' => $methodeLivraisonIri,
				'panier' => $panierIri,
				'transporteur' => $transporteurIri,
				'total_produits_commande' => $prixTotalPanier
			]
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		$commandeIri = $responseCommande->toArray()['@id'];
		echo "\n--- 10. Valider la commande ---\n";
		
		// 11. Vérifier le détail de la commande
		$responseRecap = $client->request('GET', $commandeIri);
		$commandeData = $responseRecap->toArray();
		$this->assertEquals($transporteurIri, $commandeData['transporteur']["@id"], 'Le transporteur de la commande est incorrect.');
		$this->assertNotEmpty($commandeData['commandeProduits'], 'La commande ne contient pas de produits.');
		echo "\n--- 11. Vérification du détail de la commande ---\n";
		
		// 12. Vérifier l'état de la commande après paiement
		$updatedCommandeResponse = $client->request('GET', $commandeIri);
		$updatedCommandeData = $updatedCommandeResponse->toArray();
		$this->assertEquals('Commande Payée', $updatedCommandeData['etat_commande']['libelle'], 'L\'état de la commande n\'a pas été mis à jour après le paiement.');
		echo "\n--- 12. Vérification de l'état de la commande après le paiement ---\n";

		// 13. Vérifier que l'état du panier est "fermé" après la création de la commande
		$responsePanier = $client->request('GET', $panierIri);
		$panierData = $responsePanier->toArray();
		$this->assertEquals('ferme', $panierData['etat'], 'L\'état du panier n\'a pas été mis à jour en "fermé" après la création de la commande.');
		echo "\n--- 13. Vérification de l'état du panier après la création de la commande ---\n";

		// 14. Vérifier que le stock du produit a été mis à jour après la commande
		$responseProduit = $client->request('GET', $produitIri);
		$produitData = $responseProduit->toArray();
		$this->assertEquals(8, $produitData['stock'], 'Le stock du produit n\'a pas été mis à jour après la commande.');
		echo "\n--- 14. Vérification du stock du produit après la commande ---\n";
	}
}
