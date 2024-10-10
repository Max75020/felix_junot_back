<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;

class CommandeCompleteTest extends TestAuthentificator
{
	public function testCompleteOrderProcess()
	{
		// 1. Créer un client authentifié pour l'utilisateur
		$client = $this->createAuthenticatedClient();
		echo "\n--- 1. Créer un client authentifié pour l'utilisateur ---\n";

		// 2. Récupérer l'IRI de l'utilisateur connecté
		$utilisateurIri = $this->getUserIri($client);
		echo "\n--- 2. Récupérer l'IRI de l'utilisateur connecté ---\n";

		// 3. Créer un panier pour l'utilisateur
		$responsePanier = $client->request('POST', '/api/paniers', [
			'json' => [
				'utilisateur' => $utilisateurIri,
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		$panierIri = $responsePanier->toArray()['@id'];
		echo "\n--- 3. Créer un panier pour l'utilisateur ---\n";

		// 4. Créer un produit
		$produitIri = $this->createProduit();
		echo "\n--- 4. Créer un produit ---\n";

		// 5. Ajouter le produit au panier_produit avec une quantité de 2
		$responsePanierProduit = $client->request('POST', '/api/panier_produits', [
			'json' => [
				'panier' => $panierIri,
				'produit' => $produitIri,
				'quantite' => 2
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		echo "\n--- 5. Ajouter le produit au panier avec une quantité de 2 ---\n";

		// 6. Vérifier que le produit a bien été ajouté au panier avec la bonne quantité
		$responsePanierVerification = $client->request('GET', $panierIri);
		$panierData = $responsePanierVerification->toArray();
		$this->assertCount(1, $panierData['panierProduits'], 'Le produit n\'a pas été ajouté correctement au panier.');
		$this->assertEquals(2, $panierData['panierProduits'][0]['quantite'], 'La quantité de produit dans le panier est incorrecte.');
		echo "\n--- 6. Vérifier que le produit a bien été ajouté au panier avec la bonne quantité ---\n";

		// 7. Créer une adresse pour la commande
		$adresseIri = $this->createAdresse($client, $utilisateurIri);
		echo "\n--- 7. Créer une adresse pour la commande ---\n";

		// 8. Créer un transporteur pour la commande
		$transporteurIri = $this->createTransporteur();
		echo "\n--- 8. Créer un transporteur pour la commande ---\n";

		// 9. Créer un état de commande pour la commande
		$etatCommandeIri = $this->createEtatCommandeTest();
		echo "\n--- 9. Créer un état de commande pour la commande ---\n";

		// 10. Valider le panier et créer une commande
		$responseCommande = $client->request('POST', '/api/commandes', [
			'json' => [
				'utilisateur' => $utilisateurIri,
				'adresse_livraison' => $adresseIri,
				'adresse_facturation' => $adresseIri,
				'transporteur' => $transporteurIri,
				'panier' => $panierIri,
				'etat_commande' => $etatCommandeIri
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		$commandeIri = $responseCommande->toArray()['@id'];
		echo "\n--- 10. Valider le panier et créer une commande ---\n";

		// 11. Vérifier le détail de la commande pour s'assurer que tout est correct
		$responseRecap = $client->request('GET', $commandeIri);
		$commandeData = $responseRecap->toArray();
		$this->assertEquals($transporteurIri, $commandeData['transporteur']);
		$this->assertNotEmpty($commandeData['produits'], 'La commande ne contient pas de produits.');
		echo "\n--- 11. Vérifier le détail de la commande pour s'assurer que tout est correct ---\n";

		// 12. Simuler le paiement de la commande
		// (Simuler un appel à l'API de paiement pour valider le paiement ici)
		echo "\n--- 12. Simuler le paiement de la commande ---\n";

		// 13. Vérifier l'état de la commande après le paiement
		$updatedCommandeResponse = $client->request('GET', $commandeIri);
		$updatedCommandeData = $updatedCommandeResponse->toArray();
		$this->assertEquals('Commande payée', $updatedCommandeData['etat_commande'], 'L\'état de la commande n\'a pas été mis à jour après le paiement.');
		echo "\n--- 13. Vérifier l'état de la commande après le paiement ---\n";
	}
}
