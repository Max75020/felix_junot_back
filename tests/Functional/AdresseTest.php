<?php

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class AdresseTest extends ApiTestCase
{
	private ?string $jwtToken = null;
	private ?string $utilisateurIri = null;

	// Méthode pour obtenir le jeton JWT
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
				// 'token_reinitialisation' => null, // Si nécessaire
			],
		]);

		// Vérifier que l'utilisateur a été créé avec succès
		$this->assertResponseStatusCodeSame(201);

		$data = $response->toArray();
		$this->utilisateurIri = $data['@id'];

		// Effectuer la requête d'authentification pour obtenir le jeton
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $email,
				'password' => $password,
			],
		]);

		$this->assertResponseIsSuccessful();

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

	// Méthode pour créer un client authentifié
	private function createAuthenticatedClient()
	{
		$token = $this->getToken();

		return static::createClient([], [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		]);
	}

	// Méthode pour créer une adresse
	private function createAdresse($client, $utilisateurIri): string
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

		$this->assertResponseStatusCodeSame(201);
		$data = $response->toArray();
		return $data['@id'];
	}

	public function testGetCollection(): void
	{
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;

		$this->createAdresse($client, $utilisateurIri);

		$response = $client->request('GET', '/api/adresses');

		$this->assertResponseIsSuccessful();
		$data = $response->toArray();

		$this->assertGreaterThan(0, $data['hydra:totalItems']);
		$this->assertJsonContains(['@context' => '/api/contexts/Adresse']);
	}

	public function testGetAdresse(): void
	{
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		$response = $client->request('GET', $adresseIri);

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains([
			'prenom' => 'John',
			'nom' => 'Doe',
			'type' => 'Facturation',
			'rue' => '123 Main St',
			'code_postal' => '75001',
			'ville' => 'Paris',
			'pays' => 'France',
		]);
	}

	public function testCreateAdresse(): void
	{
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		$this->assertNotEmpty($adresseIri);
		$this->assertResponseStatusCodeSame(201);
	}

	public function testUpdatePatchAdresse(): void
	{
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		$client->request('PATCH', $adresseIri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			],
			'json' => [
				'prenom' => 'Jane',
			],
		]);

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains(['prenom' => 'Jane']);
	}

	public function testUpdatePutAdresse(): void
	{
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

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

		$this->assertResponseIsSuccessful();
		$this->assertJsonContains(['prenom' => 'Jane']);
	}



	public function testDeleteAdresse(): void
	{
		$client = $this->createAuthenticatedClient();
		$utilisateurIri = $this->utilisateurIri;
		$adresseIri = $this->createAdresse($client, $utilisateurIri);

		$client->request('DELETE', $adresseIri);

		$this->assertResponseStatusCodeSame(204);

		// Vérifier que l'adresse n'existe plus
		$client->request('GET', $adresseIri);
		$this->assertResponseStatusCodeSame(404);
	}
}
