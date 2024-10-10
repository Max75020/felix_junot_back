<?php

namespace App\Tests\Functional;

use App\Tests\Authentificator\TestAuthentificator;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Functional\UtilisateurTest;
use App\Tests\Functional\EtatCommandeTest;
use ApiPlatform\Symfony\Bundle\Test\Client;

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
