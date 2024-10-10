<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;

class AdresseTest extends TestAuthentificator
{
	/**
	 * Teste la récupération de la collection d'adresses
	 */
	public function testGetCollection(): void
	{
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard
		$utilisateurIri = $this->getUserIri($client);

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
		$this->assertArrayHasKey('@context', $data, 'La clé @context est absente.');
		$this->assertEquals('/api/contexts/Adresse', $data['@context'], 'Le contexte API n\'est pas correct.');
	}

	/**
	 * Teste la récupération d'une adresse spécifique
	 */
	public function testGetAdresse(): void
	{
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard
		$utilisateurIri = $this->getUserIri($client);

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
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard
		$utilisateurIri = $this->getUserIri($client);

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
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard
		$utilisateurIri = $this->getUserIri($client);

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
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard
		$utilisateurIri = $this->getUserIri($client);

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
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient(); // Utilisateur standard
		$utilisateurIri = $this->getUserIri($client);

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
