<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;

class CategorieTest extends TestAuthentificator
{

	/**
	 * Teste la création d'une catégorie en tant qu'administrateur.
	 */
	public function testCreateCategorieAsAdmin(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$categorieIri = $this->createCategorie($client);

		// Vérifie que l'Iri de la catégorie créée n'est pas vide
		$this->assertNotEmpty($categorieIri, 'L\'IRI de la catégorie créée est vide.');
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le statut HTTP n\'est pas 201 Created.');
	}

	/**
	 * Teste la récupération de la collection de catégories.
	 */
	public function testGetCollection(): void
	{

		// Créer un client authentifié en tant qu'administrateur
		$adminClient = $this->createAuthenticatedClient(true);
		// Créer une catégorie en tant qu'administrateur
		$categorieIri = $this->createCategorie($adminClient);

		// Vérifie que l'Iri de la catégorie créée n'est pas vide
		$this->assertNotEmpty($categorieIri, 'L\'IRI de la catégorie créée est vide.');
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le statut HTTP n\'est pas 201 Created.');

		// Utiliser un client authentifié en tant qu'utilisateur standard pour éviter l'erreur 401 :  Absence de jeton JWT
		$client = $this->createAuthenticatedClient();

		$response = $client->request('GET', '/api/categories');

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de la collection de catégories a échoué.');
		$data = $response->toArray();

		// Vérifie la présence de la clé 'hydra:totalItems'
		$this->assertArrayHasKey('hydra:totalItems', $data, 'La clé hydra:totalItems est absente.');

		// Vérifie que la collection n'est pas vide
		$this->assertGreaterThan(0, $data['hydra:totalItems'], 'La collection des catégories est vide.');

		// Vérifie la présence de la clé '@context' et sa valeur
		$this->assertArrayHasKey('@context', $data, 'La clé @context est absente.');
		$this->assertEquals('/api/contexts/Categorie', $data['@context'], 'Le contexte API n\'est pas correct.');
	}

	/**
	 * Teste que la création d'une catégorie par un utilisateur standard est interdite.
	 */
	public function testCreateCategorieAsUserForbidden(): void
	{
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard

		$client->request('POST', '/api/categories', [
			'json' => [
				'nom' => 'Test Categorie ' . uniqid(),
			],
		]);

		// Vérifie que la réponse est 403 Forbidden
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu créer une catégorie.');
	}

	/**
	 * Teste la mise à jour partielle d'une catégorie en tant qu'administrateur.
	 */
	public function testUpdatePatchCategorieAsAdmin(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$categorieIri = $this->createCategorie($client);

		$nouveauNom = 'Updated Categorie ' . uniqid();

		$client->request('PATCH', $categorieIri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			],
			'json' => [
				'nom' => $nouveauNom,
			],
		]);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PATCH de la catégorie a échoué.');

		// Vérifie que le nom a été mis à jour correctement
		$this->assertJsonContains(['nom' => $nouveauNom], 'Le nom de la catégorie n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la mise à jour complète d'une catégorie en tant qu'administrateur.
	 */
	public function testUpdatePutCategorieAsAdmin(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$categorieIri = $this->createCategorie($client);

		$nouveauNom = 'Updated Categorie ' . uniqid();

		$client->request('PUT', $categorieIri, [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => [
				'nom' => $nouveauNom,
			],
		]);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PUT de la catégorie a échoué.');

		// Vérifie que le nom a été mis à jour correctement
		$this->assertJsonContains(['nom' => $nouveauNom], 'Le nom de la catégorie n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la suppression d'une catégorie en tant qu'administrateur.
	 */
	public function testDeleteCategorieAsAdmin(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$categorieIri = $this->createCategorie($client);

		$client->request('DELETE', $categorieIri);

		// Vérifie que la suppression a réussi avec un statut 204 No Content
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de la catégorie a échoué.');

		// Vérifie que la catégorie n'existe plus
		$client->request('GET', $categorieIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'La catégorie n\'a pas été supprimée correctement.');
	}

	/**
	 * Teste que la suppression d'une catégorie par un utilisateur standard est interdite.
	 */
	public function testDeleteCategorieAsUserForbidden(): void
	{
		// Créer un client authentifié en tant qu'administrateur pour créer la catégorie
		$adminClient = $this->createAuthenticatedClient(true); // Administrateur
		$categorieIri = $this->createCategorie($adminClient); // Créer la catégorie en tant qu'administrateur

		// Créer un client authentifié en tant qu'utilisateur standard pour tenter de supprimer la catégorie
		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$userClient->request('DELETE', $categorieIri);

		// Vérifie que la suppression par un utilisateur standard est interdite avec un statut 403 Forbidden
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu supprimer une catégorie.');
	}
}
