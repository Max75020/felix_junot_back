<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\Symfony\Bundle\Test\Client;

class CategorieTest extends ApiTestCase
{
	private ?string $userToken = null;
	private ?string $adminToken = null;

	/**
	 * Obtient le jeton JWT d'un utilisateur simple
	 *
	 * @return string Le jeton JWT de l'utilisateur
	 */
	private function getUserToken(): string
	{
		if ($this->userToken) {
			return $this->userToken;
		}

		$client = static::createClient();

		// Créer un utilisateur de test
		$email = 'test.user.' . uniqid() . '@example.com';
		$password = 'Password+75020';

		$response = $client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Test',
				'nom' => 'User',
				'email' => $email,
				'telephone' => '0668747201',
				'role' => 'ROLE_USER',
				'password' => $password,
				'email_valide' => true,
			],
		]);

		// Vérifie que l'utilisateur a été créé avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'utilisateur de test n\'a pas été créé correctement.');

		// Authentification pour obtenir le jeton
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $email,
				'password' => $password,
			],
		]);

		// Vérifie que l'authentification a réussi
		$this->assertResponseIsSuccessful('L\'authentification de l\'utilisateur de test a échoué.');

		// Récupère les données de la réponse
		$data = $response->toArray();
		$this->userToken = $data['token'] ?? $data['jwt'] ?? null;

		// Vérifie que le jeton JWT a été récupéré
		if (!$this->userToken) {
			$this->fail('Le jeton JWT n\'a pas été trouvé dans la réponse.');
		}

		return $this->userToken;
	}

	/**
	 * Obtient le jeton JWT d'un administrateur
	 *
	 * @return string Le jeton JWT de l'administrateur
	 */
	private function getAdminToken(): string
	{
		if ($this->adminToken) {
			return $this->adminToken;
		}

		$client = static::createClient();

		// Créer un administrateur de test
		$email = 'test.admin.' . uniqid() . '@example.com';
		$password = 'Password+75020';

		$response = $client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Admin',
				'nom' => 'User',
				'email' => $email,
				'telephone' => '0668747201',
				'role' => 'ROLE_ADMIN',
				'password' => $password,
				'email_valide' => true,
			],
		]);

		// Vérifie que l'administrateur a été créé avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'administrateur de test n\'a pas été créé correctement.');

		// Authentification pour obtenir le jeton
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $email,
				'password' => $password,
			],
		]);

		// Vérifie que l'authentification a réussi
		$this->assertResponseIsSuccessful('L\'authentification de l\'administrateur de test a échoué.');

		// Récupère les données de la réponse
		$data = $response->toArray();
		$this->adminToken = $data['token'] ?? $data['jwt'] ?? null;

		// Vérifie que le jeton JWT a été récupéré
		if (!$this->adminToken) {
			$this->fail('Le jeton JWT de l\'administrateur n\'a pas été trouvé dans la réponse.');
		}

		return $this->adminToken;
	}

	/**
	 * Crée un client authentifié
	 *
	 * @param bool $admin Indique si le client doit être un administrateur
	 * @return \ApiPlatform\Symfony\Bundle\Test\Client Le client authentifié
	 */
	private function createAuthenticatedClient(bool $admin = false): Client
	{
		$token = $admin ? $this->getAdminToken() : $this->getUserToken();

		return static::createClient([], [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		]);
	}

	/**
	 * Crée une catégorie avec un nom unique
	 *
	 * @param \ApiPlatform\Symfony\Bundle\Test\Client $client Le client authentifié
	 * @return string L'Iri de la catégorie créée
	 */
	private function createCategorie(Client $client): string
	{
		$nomUnique = 'Test Categorie ' . uniqid();

		$response = $client->request('POST', '/api/categories', [
			'json' => [
				'nom' => $nomUnique,
			],
		]);

		// Vérifie que la catégorie a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'La catégorie n\'a pas été créée correctement.');
		$data = $response->toArray();
		return $data['@id'];
	}

	/**
	 * Teste la récupération de la collection de catégories
	 */
	public function testGetCollection(): void
	{
		// Utiliser un client authentifié pour éviter l'erreur 401
		$client = $this->createAuthenticatedClient(); // Utilisateur standard

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
	 * Teste la création d'une catégorie en tant qu'administrateur
	 */
	public function testCreateCategorieAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Client administrateur
		$categorieIri = $this->createCategorie($client);

		// Vérifie que l'Iri de la catégorie créée n'est pas vide
		$this->assertNotEmpty($categorieIri, 'L\'IRI de la catégorie créée est vide.');
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le statut HTTP n\'est pas 201 Created.');
	}

	/**
	 * Teste que la création d'une catégorie par un utilisateur standard est interdite
	 */
	public function testCreateCategorieAsUserForbidden(): void
	{
		$client = $this->createAuthenticatedClient(); // Client utilisateur standard

		$client->request('POST', '/api/categories', [
			'json' => [
				'nom' => 'Test Categorie ' . uniqid(),
			],
		]);

		// Vérifie que la réponse est 403 Forbidden
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu créer une catégorie.');
	}

	/**
	 * Teste la mise à jour partielle d'une catégorie en tant qu'administrateur
	 */
	public function testUpdatePatchCategorieAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Client administrateur
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
	 * Teste la mise à jour complète d'une catégorie en tant qu'administrateur
	 */
	public function testUpdatePutCategorieAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Client administrateur
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
	 * Teste la suppression d'une catégorie en tant qu'administrateur
	 */
	public function testDeleteCategorieAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Client administrateur
		$categorieIri = $this->createCategorie($client);

		$client->request('DELETE', $categorieIri);

		// Vérifie que la suppression a réussi avec un statut 204 No Content
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de la catégorie a échoué.');

		// Vérifie que la catégorie n'existe plus
		$client->request('GET', $categorieIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'La catégorie n\'a pas été supprimée correctement.');
	}

	/**
	 * Teste que la suppression d'une catégorie par un utilisateur standard est interdite
	 */
	public function testDeleteCategorieAsUserForbidden(): void
	{
		// Client administrateur pour créer la catégorie
		$adminClient = $this->createAuthenticatedClient(true); // Client administrateur
		$categorieIri = $this->createCategorie($adminClient); // Créer la catégorie en tant qu'administrateur

		// Client utilisateur standard pour tenter de supprimer la catégorie
		$userClient = $this->createAuthenticatedClient(); // Client utilisateur standard
		$userClient->request('DELETE', $categorieIri);

		// Vérifie que la suppression par un utilisateur standard est interdite avec un statut 403 Forbidden
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu supprimer une catégorie.');
	}
}
