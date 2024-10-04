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

	// Méthode pour créer un produit
	public function createProduit()
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAdminClient();

		// Créer une catégorie
		$categorieNom = 'Catégorie Test' . uniqid();
		// Effectuer une requête POST pour créer une nouvelle catégorie
		$responseCategorie = $client->request('POST', '/api/categories', [
			'json' => [
				'nom' => $categorieNom
			]
		]);
		// Vérifier que la catégorie a été créée avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseCategorie->getStatusCode());
		// Récupérer les données de la catégorie créée sous forme de tableau
		$responseCategorieArray = $responseCategorie->toArray();
		// Récupérer l'IRI de la catégorie créée
		$categorieIri = $responseCategorieArray['@id'];

		// Créer une TVA
		$responseTva = $client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '20.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseTva->getStatusCode());
		// Récupérer les données de la TVA créée sous forme de tableau
		$responseTvaArray = $responseTva->toArray();
		// Récupérer l'IRI de la TVA créée
		$tvaIri = $responseTvaArray['@id'];

		// Créer un produit
		$produitNom = 'Produit Test' . uniqid();
		// Description du produit à créer
		$produitDescription = 'Description du produit test' . uniqid();
		// Reference du produit à créer
		$produit = new Produit();
		$produitReference = $produit->generateProductReference();
		// Effectuer une requête POST pour créer un nouveau produit
		$responseProduit = $client->request('POST', '/api/produits', [
			'json' => [
				'reference' => $produitReference,
				'nom' => $produitNom,
				'description' => $produitDescription,
				'prix' => '99.99',
				'tva' => $tvaIri,
				'categories' => [$categorieIri]
			]
		]);
		// Vérifier que le produit a été créé avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseProduit->getStatusCode());

		// Retourner l'IRI du produit créé
		return $responseProduit->toArray()['@id'];
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
		// Créer un client authentifié en tant qu'utilisateur
		$client = $this->createUserClient();
		// Créer un produit pour avoir un produit à récupérer
		$produitIri = $this->createProduit();
		// Effectuer une requête GET pour récupérer les données du produit créé
		$response = $client->request('GET', $produitIri);

		// Vérifier que la réponse a un code de statut HTTP 200 (OK)
		$this->assertSame(Response::HTTP_OK, $response->getStatusCode(), 'Le statut HTTP de la réponse n\'est pas 200 OK.');

		// Réponse de la requête sous forme de tableau
		$data = $response->toArray();
		// Vérifier que le nom du produit est présent dans la réponse
		$this->assertArrayHasKey('nom', $data, 'Le nom du produit est absent de la réponse.');
		// Vérifier que le nom du produit n'est pas vide
		$this->assertNotEmpty($data['nom'], 'Le nom du produit est vide.');
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
