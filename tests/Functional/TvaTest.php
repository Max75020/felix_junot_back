<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;

class TvaTest extends TestAuthentificator
{
	public function testAdminCanCreateTva(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true);
		// Effectuer une requête POST pour créer une nouvelle TVA
		$client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '20.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
	}

	public function testAdminCanUpdateTva(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true);
		//Créer une TVA
		$response = $client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '5.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		// Vérifier que la réponse contient les données de la TVA créée sous forme de tableau
		$dataArray = $response->toArray();
		// Vérifier que le tableau contient l'IRI de la TVA créée
		$this->assertArrayHasKey('@id', $dataArray, 'L\'IRI de l\'utilisateur créé est absente.');
		// Récupérer l'IRI de la TVA créée
		$iriTva = $dataArray['@id'];
		// Vérifier que le tableau contient le taux de la TVA créée
		$this->assertArrayHasKey('taux', $dataArray, 'Le taux de la TVA créée est absent.');
		// Effectuer une requête PUT pour mettre à jour la TVA créée
		$client->request('PUT', $iriTva, [
			'json' => [
				'taux' => '15.00'
			]
		]);
		// Vérifier que la TVA a été mise à jour avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_OK);
	}

	public function testAdminCanDeleteTva(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true);
		//Créer une TVA
		$response = $client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '8.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		// Vérifier que la réponse contient les données de la TVA créée sous forme de tableau
		$dataArray = $response->toArray();
		// Vérifier que le tableau contient l'IRI de la TVA créée
		$this->assertArrayHasKey('@id', $dataArray, 'L\'IRI de l\'utilisateur créé est absente.');
		// Récupérer l'IRI de la TVA créée
		$iriTva = $dataArray['@id'];
		// Vérifier que le tableau contient le taux de la TVA créée
		$this->assertArrayHasKey('taux', $dataArray, 'Le taux de la TVA créée est absent.');
		// Effectuer une requête DELETE pour supprimer la TVA créée
		$client->request('DELETE', $iriTva);
		// Vérifier que la TVA a été supprimée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
		// Vérifier que la TVA n'existe plus
		$client->request('GET', $iriTva);
		// Vérifier que la TVA n'existe plus
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
	}

	public function testAdminCanGetTva(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true);
		//Créer une TVA
		$response = $client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '10.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		// Vérifier que la réponse contient les données de la TVA créée sous forme de tableau
		$dataArray = $response->toArray();
		// Vérifier que le tableau contient l'IRI de la TVA créée
		$this->assertArrayHasKey('@id', $dataArray, 'L\'IRI de l\'utilisateur créé est absente.');
		// Récupérer l'IRI de la TVA créée
		$iriTva = $dataArray['@id'];
		// Vérifier que le tableau contient le taux de la TVA créée
		$this->assertArrayHasKey('taux', $dataArray, 'Le taux de la TVA créée est absent.');
		// Vérifier que la TVA existe
		$client->request('GET', $iriTva);
		// Vérifier que la TVA a été obtenue avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_OK);
	}

	public function testAdminCanGetTvaCollection(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true);
		// Effectuer une requête GET pour obtenir une collection de TVA
		$client->request('GET', '/api/tvas');
		// Vérifier que la collection de TVA a été obtenue avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_OK);
	}

	public function testUserCanGetTva(): void
	{
		// Créer un administrateur
		$client = $this->createAuthenticatedClient(true);
		//Créer une TVA
		$response = $client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '10.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		// Récupérer l'IRI de la TVA créée
		$iriTva = $response->toArray()['@id'];
		// Se déconnecter
		$client->request('POST', '/api/logout');

		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient();
		// Vérifier que la TVA existe avec un utilisateur standard
		$client->request('GET', $iriTva);
		// Vérifier que la TVA a été obtenue avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_OK);
	}

	public function testUserCannotCreateTva(): void
	{
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient();
		// Effectuer une requête POST pour créer une nouvelle TVA
		$client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '20.00'
			]
		]);
		// Vérifier que la création de la TVA a échoué
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testUserCannotUpdateTva(): void
	{
		// Créer un client authentifié en tant qu'administrateur pour créer une TVA
		$adminClient = $this->createAuthenticatedClient(true);
		// Créer une TVA pour le test
		$response = $adminClient->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '20.00'
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		$tvaData = $response->toArray();
		$tvaIri = $tvaData['@id'];

		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient();
		// Effectuer une requête PUT pour mettre à jour la TVA existante
		$client->request('PUT', $tvaIri, [
			'json' => [
				'taux' => '15.00'
			]
		]);
		// Vérifier que la mise à jour de la TVA a échoué avec un code 403
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}


	public function testUserCannotDeleteTva(): void
	{
		// Créer un client authentifié en tant qu'administrateur pour créer une TVA
		$adminClient = $this->createAuthenticatedClient(true);
		// Créer une TVA pour le test
		$response = $adminClient->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '20.00'
			]
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
		$tvaData = $response->toArray();
		$tvaIri = $tvaData['@id'];

		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient();
		// Effectuer une requête DELETE pour supprimer la TVA existante
		$client->request('DELETE', $tvaIri);
		// Vérifier que la suppression de la TVA a échoué avec un code 403
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}

	public function testUserCannotGetTvaCollection(): void
	{
		// Créer un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClient();
		// Effectuer une requête GET pour obtenir une collection de TVA
		$client->request('GET', '/api/tvas');
		// Vérifier que l'obtention de la collection de TVA a échoué
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
	}
}
