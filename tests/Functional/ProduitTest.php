<?php


namespace App\Tests\Functional;


use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;
use App\Entity\Produit;

class ProduitTest extends TestAuthentificator
{

	// Méthode pour créer un client authentifié en tant qu'administrateur
	private function createAdminClient()
	{
		return $this->createAuthenticatedClient(true);
	}

	// Méthode pour créer un client authentifié en tant qu'utilisateur
	private function createUserClient()
	{
		return $this->createAuthenticatedClient();
	}

	// Teste la récupération de la collection de produits en tant qu'utilisateur
	public function testGetCollectionAsUser()
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAdminClient();
		// Créer une catégorie pour avoir au moins une catégorie dans la collection
		$categorieNom = 'Catégorie Test' . uniqid();
		// Effectuer une requête POST pour créer une nouvelle catégorie
		$responseCategorie = $client->request('POST', '/api/categories', [
			'json' => [
				'nom' => $categorieNom
			]
		]);
		// Vérifier que la catégorie a été créée avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseCategorie->getStatusCode());

		// Créer un client authentifié en tant qu'utilisateur
		$client = $this->createUserClient();
		// Effectuer une requête GET pour récupérer la collection de produits
		$client->request('GET', '/api/produits');
		// Vérifier que la requête a été effectuée avec succès
		$this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
	}

	// Teste la récupération d'un produit spécifique en tant qu'utilisateur
	public function testGetAsUser()
	{
		echo "\n1. Création d'un client authentifié en tant qu'utilisateur...\n";
		// Créer un client authentifié en tant qu'utilisateur
		$client = $this->createUserClient();

		echo "2. Création d'un produit pour le test...\n";
		// Créer un produit pour avoir un produit à récupérer
		$produitIri = $this->createProduit();
		echo "Produit créé avec succès. IRI du produit : $produitIri\n";

		echo "3. Requête GET pour récupérer les données du produit créé...\n";
		// Effectuer une requête GET pour récupérer les données du produit créé
		$response = $client->request('GET', $produitIri);

		echo "4. Vérification du statut HTTP de la réponse...\n";
		// Vérifier que la réponse a un code de statut HTTP 200 (OK)
		$this->assertSame(Response::HTTP_OK, $response->getStatusCode(), 'Le statut HTTP de la réponse n\'est pas 200 OK.');

		echo "5. Conversion de la réponse en tableau...\n";
		// Réponse de la requête sous forme de tableau
		$data = $response->toArray();

		echo "6. Vérification que le nom du produit est présent dans la réponse...\n";
		// Vérifier que le nom du produit est présent dans la réponse
		$this->assertArrayHasKey('nom', $data, 'Le nom du produit est absent de la réponse.');

		echo "7. Vérification que le nom du produit n'est pas vide...\n";
		// Vérifier que le nom du produit n'est pas vide
		$this->assertNotEmpty($data['nom'], 'Le nom du produit est vide.');
		echo "Test terminé avec succès.\n";
	}


	// Teste la récupération de la collection de produits en tant qu'administrateur
	public function testGetCollectionAsAdmin()
	{
		$client = $this->createAdminClient();
		// Créer un produit pour avoir au moins un produit dans la collection
		$this->createProduit();
		// Effectuer une requête GET pour récupérer la collection de produits
		$responseGet = $client->request('GET', '/api/produits');
		// Vérifier que la requête a été effectuée avec succès
		$this->assertSame(Response::HTTP_OK, $responseGet->getStatusCode(), 'Le statut HTTP de la réponse n\'est pas 200 OK.');
	}

	// Teste la récupération d'un produit spécifique en tant qu'administrateur
	public function testGetAsAdmin()
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAdminClient();
		// Créer un produit pour avoir un produit à récupérer
		$produitIri = $this->createProduit();
		// Effectuer une requête GET pour récupérer les données du produit créé
		$responseGet = $client->request('GET', $produitIri);
		// Vérifier que la requête a été effectuée avec succès
		$this->assertSame(Response::HTTP_OK, $responseGet->getStatusCode(), 'Le statut HTTP de la réponse n\'est pas 200 OK.');
	}

	// Teste la création d'un produit en tant qu'administrateur
	public function testPostAsAdmin()
	{
		$this->createProduit();
	}

	// Teste la modification d'un produit en tant qu'administrateur
	public function testPatchAsAdmin()
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAdminClient();
		// Créer un produit pour avoir un produit à modifier
		$produitIri = $this->createProduit();
		// Effectuer une requête PATCH pour modifier partiellement les données du produit créé
		$client->request('PATCH', $produitIri, [
			'json' => [
				'nom' => 'Produit Test Modifié Partiellement',
			],
			'headers' => ['Content-Type' => 'application/merge-patch+json']
		]);
		// Vérifier que la requête a été effectuée avec succès
		$this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
	}

	// Teste la suppression d'un produit en tant qu'administrateur
	public function testDeleteAsAdmin()
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAdminClient();
		// Créer un produit pour avoir un produit à supprimer
		$produitIri = $this->createProduit();
		// Effectuer une requête DELETE pour supprimer le produit créé
		$client->request('DELETE', $produitIri);
		// Vérifier que la requête a été effectuée avec succès
		$this->assertSame(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
		// Effectuer une requête GET pour récupérer les données du produit supprimé
		$client->request('GET', $produitIri);
		// Vérifier que le produit n'existe plus
		$this->assertSame(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
	}
}
