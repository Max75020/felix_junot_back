<?php

namespace App\Tests\Functional;

use App\Tests\Authentificator\TestAuthentificator;
use Symfony\Component\HttpFoundation\Response;

class PanierTest extends TestAuthentificator
{
	public function testAddProductToCart()
	{
		// Créer un utilisateur authentifié
		$client = $this->createAuthenticatedClient();
		echo "\n--- 1. Création d'un utilisateur authentifié ---\n";

		// Récupérer l'IRI de l'utilisateur connecté
		$utilisateurIri = $this->getUserIri($client);
		echo "\n--- 2. Récupération de l'IRI de l'utilisateur connecté ---\n";

		// Créer un produit
		$produitIri = $this->createProduit();
		echo "\n--- 3. Création d'un produit ---\n";

		// Ajouter le produit au panier de l'utilisateur (logique d'ajout automatique du panier)
		$response = $client->request('POST', '/api/panier_produits', [
			'json' => [
				'produit' => $produitIri,
				'quantite' => 2,
			],
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le produit n\'a pas été ajouté au panier.');
		echo "\n--- 4. Ajout du produit au panier avec une quantité de 2 ---\n";

		// Vérifier que le panier a bien été créé automatiquement pour l'utilisateur
		$responsePanier = $client->request('GET', '/api/paniers');
		$paniers = $responsePanier->toArray();
		$this->assertCount(1, $paniers, 'Le panier n\'a pas été créé automatiquement.');
		echo "\n--- 5. Vérification que le panier a bien été créé automatiquement ---\n";

		// Vérifier que le produit est bien présent dans le panier avec la bonne quantité
		$panierIri = $paniers[0]['@id'];
		$responsePanierProduit = $client->request('GET', $panierIri);
		$panierData = $responsePanierProduit->toArray();
		$this->assertCount(1, $panierData['panierProduits'], 'Le produit n\'a pas été ajouté correctement au panier.');
		$this->assertEquals(2, $panierData['panierProduits'][0]['quantite'], 'La quantité de produit dans le panier est incorrecte.');
		echo "\n--- 6. Vérification que le produit est présent dans le panier avec la bonne quantité ---\n";
	}
}
