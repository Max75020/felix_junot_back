	public function testCompleteOrderProcess()
	{
		// 1. Créer un client authentifié pour l'utilisateur
		$client = $this->createAuthenticatedClient();

		// récupérer l'IRI de l'utilisateur connecté
		$utilisateurIri = $this->getUserIri($client);

		// 2. Ajouter un produit au panier
		$responsePanier = $client->request('POST', '/api/paniers', [
			'json' => [
				'utilisateur' => $utilisateurIri,
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		$panierIri = $responsePanier->toArray()['@id'];

		// 3. Ajouter un produit à la table panier_produit
		$responsePanierProduit = $client->request('POST', '/api/panier_produits', [
			'json' => [
				'panier' => $panierIri,
				'produit' => '/api/produits/1', // URI du produit ajouté
				'quantite' => 1
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		// 4. Valider le panier et créer une commande
		$responseCommande = $client->request('POST', '/api/commandes', [
			'json' => [
				'utilisateur' => '/api/utilisateurs/1',
				'adresse_livraison' => '/api/adresses/1',
				'adresse_facturation' => '/api/adresses/1',
				'transporteur' => 'Colissimo',
				'panier' => $panierIri
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		$commandeIri = $responseCommande->toArray()['@id'];

		// 5. Vérifier les détails du récapitulatif de la commande
		$responseRecap = $client->request('GET', $commandeIri);
		$commandeData = $responseRecap->toArray();
		$this->assertEquals('Colissimo', $commandeData['transporteur']);
		$this->assertNotEmpty($commandeData['produits']);

		// 6. Simuler le paiement via Stripe
		// (Simuler un appel à l'API Stripe pour valider le paiement ici)

		// 7. Vérifier l'état de la commande après le paiement
		$updatedCommandeResponse = $client->request('GET', $commandeIri);
		$updatedCommandeData = $updatedCommandeResponse->toArray();
		$this->assertEquals('Commande payée', $updatedCommandeData['etat_commande']);
	}