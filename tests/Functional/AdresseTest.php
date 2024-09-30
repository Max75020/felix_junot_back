<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\Symfony\Bundle\Test\Client;

class AdresseTest extends ApiTestCase
{
	private ?string $jwtToken = null;
	private ?string $utilisateurIri = null;

	/**
	 * Méthode pour obtenir le jeton JWT
	 *
	 * @return string Le jeton JWT de l'utilisateur
	 */
	private function getToken(): string
	{
		if ($this->jwtToken) {
			return $this->jwtToken;
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

		// Vérifier que l'utilisateur a été créé avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'utilisateur de test n\'a pas été créé correctement.');

		// Récupérer les données de la réponse
		$data = $response->toArray();
		$this->utilisateurIri = $data['@id'] ?? null;

		// Authentification pour obtenir le jeton
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $email,
				'password' => $password,
			],
		]);

		// Vérifier que l'authentification a réussi
		$this->assertResponseIsSuccessful('L\'authentification de l\'utilisateur de test a échoué.');

		// Extraire le token de la réponse
		$data = $response->toArray();

		// Le token JWT est souvent dans le champ 'token' ou 'jwt'
		$this->jwtToken = $data['token'] ?? $data['jwt'] ?? null;

		if (!$this->jwtToken) {
			// Pour le débogage, afficher la réponse
			fwrite(STDERR, "Réponse de l'authentification : " . print_r($data, true));
			$this->fail('JWT token non trouvé dans la réponse');
		}

		return $this->jwtToken;
	}

	/**
	 * Méthode pour créer un client authentifié
	 *
	 * @return \ApiPlatform\Symfony\Bundle\Test\Client Le client authentifié
	 */
	private function createAuthenticatedClient(): Client
	{
		$token = $this->getToken();

		return static::createClient([], [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		]);
	}

	/**
	 * Méthode pour créer une adresse
	 *
	 * @param \ApiPlatform\Symfony\Bundle\Test\Client $client Le client authentifié
	 * @param string $utilisateurIri L'Iri de l'utilisateur
	 * @return string L'Iri de l'adresse créée
	 */
	private function createAdresse(Client $client, string $utilisateurIri): string
	{
		$response = $client->request('POST', '/api/adresses', [
			'json' => [
				'type' => 'Facturation',
				'prenom' => 'John',
				'nom' => 'Doe',
				'rue' => '123 Main St',
				'code_postal' => '75001',
				'ville' => 'Paris',
				'pays' => 'France',
				'utilisateur' => $utilisateurIri,
			],
		]);

		// Vérifier que l'adresse a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'adresse n\'a pas été créée correctement.');
		$data = $response->toArray();
		return $data['@id'];
	}

	/**
	 * Teste la récupération de la collection d'adresses
	 */
	public function testGetCollection(): void
	{
		// Créer un client authentifié
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		// Créer une adresse de test
		$this->createAdresse($client, $utilisateurIri);

		// Effectuer une requête GET pour récupérer la collection d'adresses
		$response = $client->request('GET', '/api/adresses');

		// Vérifier que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de la collection d\'adresses a échoué.');
		$data = $response->toArray();

		// Vérifier que le nombre total d'items est supérieur à 0
		$this->assertGreaterThan(0, $data['hydra:totalItems'], 'La collection des adresses est vide.');

		// Vérifier la présence du contexte API
		$this->assertJsonContains(['@context' => '/api/contexts/Adresse'], 'Le contexte API n\'est pas correct.');
	}

	/**
	 * Teste la récupération d'une adresse spécifique
	 */
	public function testGetAdresse(): void
	{
		// Créer un client authentifié
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		// Créer une adresse de test
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		// Effectuer une requête GET pour récupérer l'adresse spécifique
		$response = $client->request('GET', $adresseIri);

		// Vérifier que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de l\'adresse a échoué.');

		// Vérifier que les données de l'adresse sont correctes
		$this->assertJsonContains([
			'prenom' => 'John',
			'nom' => 'Doe',
			'type' => 'Facturation',
			'rue' => '123 Main St',
			'code_postal' => '75001',
			'ville' => 'Paris',
			'pays' => 'France',
		], 'Les données de l\'adresse ne correspondent pas.');
	}

	/**
	 * Teste la création d'une adresse
	 */
	public function testCreateAdresse(): void
	{
		// Créer un client authentifié
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		// Créer une adresse de test
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		// Vérifier que l'Iri de l'adresse créée n'est pas vide
		$this->assertNotEmpty($adresseIri, 'L\'IRI de l\'adresse créée est vide.');
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le statut HTTP n\'est pas 201 Created.');
	}

	/**
	 * Teste la mise à jour partielle d'une adresse
	 */
	public function testUpdatePatchAdresse(): void
	{
		// Créer un client authentifié
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		// Créer une adresse de test
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		// Effectuer une requête PATCH pour mettre à jour le prénom de l'adresse
		$client->request('PATCH', $adresseIri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			],
			'json' => [
				'prenom' => 'Jane',
			],
		]);

		// Vérifier que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PATCH de l\'adresse a échoué.');

		// Vérifier que le prénom a été mis à jour correctement
		$this->assertJsonContains(['prenom' => 'Jane'], 'Le prénom de l\'adresse n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la mise à jour complète d'une adresse
	 */
	public function testUpdatePutAdresse(): void
	{
		// Créer un client authentifié
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		// Créer une adresse de test
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		// Effectuer une requête PUT pour mettre à jour l'adresse
		$client->request('PUT', $adresseIri, [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => [
				'type' => 'Facturation',
				'prenom' => 'Jane', // Nouvelle valeur
				'nom' => 'Doe',
				'rue' => '123 Main St',
				'code_postal' => '75001',
				'ville' => 'Paris',
				'pays' => 'France',
				'utilisateur' => $utilisateurIri,
			],
		]);

		// Vérifier que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PUT de l\'adresse a échoué.');

		// Vérifier que le prénom a été mis à jour correctement
		$this->assertJsonContains(['prenom' => 'Jane'], 'Le prénom de l\'adresse n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la suppression d'une adresse
	 */
	public function testDeleteAdresse(): void
	{
		// Créer un client authentifié
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		// Créer une adresse de test
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		// Effectuer une requête DELETE pour supprimer l'adresse
		$client->request('DELETE', $adresseIri);

		// Vérifier que la suppression a réussi avec un statut 204 No Content
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de l\'adresse a échoué.');

		// Vérifier que l'adresse n'existe plus
		$client->request('GET', $adresseIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'L\'adresse n\'a pas été supprimée correctement.');
	}
}
