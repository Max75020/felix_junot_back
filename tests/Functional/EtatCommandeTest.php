<?php

namespace App\Tests\Functional;

use App\Tests\Authentificator\TestAuthentificator;
use Symfony\Component\HttpFoundation\Response;

class EtatCommandeTest extends TestAuthentificator
{
	/**
	 * Crée un état de commande temporaire et retourne son IRI.
	 *
	 * @return string
	 */
	private function createEtatCommandeTest(): string
	{
		$client = $this->createAdminClient();
		$libelle = 'Test ' . uniqid();
		$client->request('POST', '/api/etat_commandes', [
			'json' => [
				'libelle' => $libelle,
			],
		]);
		$this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode(), 'La création de l\'état de commande a échoué.');

		$data = json_decode($client->getResponse()->getContent(), true);
		return $data['@id'];
	}

	private function createAdminClient()
	{
		return $this->createAuthenticatedClient(true);
	}

	private function createUserClient()
	{
		return $this->createAuthenticatedClient();
	}

	/**
	 * Teste que l'utilisateur standard peut récupérer un état de commande spécifique.
	 */
	public function testUserCanGetSpecificElement()
	{
		$client = $this->createUserClient();
		$iri = $this->createEtatCommandeTest();

		$client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'utilisateur standard ne peut pas récupérer un état de commande spécifique.');

		$data = json_decode($client->getResponse()->getContent(), true);
		$this->assertArrayHasKey('libelle', $data, 'Le champ "libelle" est absent dans la réponse.');
		$this->assertNotEmpty($data['libelle'], 'Le champ "libelle" est vide.');
	}

	/**
	 * Teste que l'utilisateur standard peut récupérer la collection des états de commande.
	 */
	public function testUserCanGetCollection()
	{
		$client = $this->createUserClient();

		// Créer un état de commande pour s'assurer qu'il y en a au moins un dans la collection
		$this->createEtatCommandeTest();

		$client->request('GET', '/api/etat_commandes');
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'utilisateur standard ne peut pas récupérer la collection des états de commande.');

		$data = json_decode($client->getResponse()->getContent(), true);
		$this->assertIsArray($data['hydra:member'], 'La réponse ne contient pas une collection valide.');
		$this->assertNotEmpty($data['hydra:member'], 'La collection des états de commande est vide.');
	}

	/**
	 * Teste que l'utilisateur standard ne peut pas créer un état de commande.
	 */
	public function testUserCannotPostElement()
	{
		// Créer un client authentifié en tant qu'utilisateur
		$client = $this->createUserClient();
		// Créer un état de commande
		$client->request('POST', '/api/etat_commandes', [
			'json' => [
				'libelle' => 'Test ' . uniqid(),
			],
		]);
		$this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode(), 'L\'utilisateur standard a pu créer un état de commande.');
	}

	/**
	 * Teste que l'utilisateur standard ne peut pas mettre à jour un état de commande via PUT.
	 */
	public function testUserCannotPutElement()
	{
		$client = $this->createUserClient();
		$iri = $this->createEtatCommandeTest();
		$libelle = 'Updated Test ' . uniqid();

		$client->request('PUT', $iri, [
			'json' => [
				'libelle' => $libelle,
			],
		]);
		$this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode(), 'L\'utilisateur standard a pu mettre à jour un état de commande via PUT.');
	}

	/**
	 * Teste que l'utilisateur standard ne peut pas modifier partiellement un état de commande via PATCH.
	 */
	public function testUserCannotPatchElement()
	{
		$client = $this->createUserClient();
		$iri = $this->createEtatCommandeTest();

		$client->request('PATCH', $iri, [], [], ['CONTENT_TYPE' => 'application/merge-patch+json'], json_encode(['libelle' => 'Updated Test ' . uniqid()]));
		$this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode(), 'L\'utilisateur standard a pu modifier partiellement un état de commande via PATCH.');
	}

	/**
	 * Teste que l'utilisateur standard ne peut pas supprimer un état de commande.
	 */
	public function testUserCannotDeleteElement()
	{
		$client = $this->createUserClient();
		$iri = $this->createEtatCommandeTest();

		$client->request('DELETE', $iri);
		$this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode(), 'L\'utilisateur standard a pu supprimer un état de commande.');
	}

	/**
	 * Teste que l'administrateur peut effectuer toutes les opérations CRUD sur les états de commande.
	 */
	public function testAdminCanPerformCrudOperations()
	{
		$client = $this->createAdminClient();

		// Création
		$libelleCreate = 'Admin Test ' . uniqid();
		$client->request('POST', '/api/etat_commandes', [
			'json' => [
				'libelle' => $libelleCreate,
			],
		]);
		$this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas créer un état de commande.');

		$dataCreate = json_decode($client->getResponse()->getContent(), true);
		$iri = $dataCreate['@id'];
		$this->assertNotEmpty($iri, 'L\'IRI de l\'état de commande créé est vide.');

		// Lecture
		$client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas récupérer un état de commande spécifique.');

		$dataRead = json_decode($client->getResponse()->getContent(), true);
		$this->assertEquals($libelleCreate, $dataRead['libelle'], 'Le libelle de l\'état de commande récupéré ne correspond pas au libelle créé.');

		// Mise à jour complète (PUT)
		$libelleUpdate = 'Updated Admin Test ' . uniqid();
		$client->request('PUT', $iri, [
			'json' => [
				'libelle' => $libelleUpdate,
			],
		]);
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas mettre à jour un état de commande via PUT.');

		// Vérification de la mise à jour via GET
		$client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas récupérer l\'état de commande après mise à jour.');
		$dataUpdate = json_decode($client->getResponse()->getContent(), true);
		$this->assertEquals($libelleUpdate, $dataUpdate['libelle'], 'Le libelle de l\'état de commande mis à jour ne correspond pas.');

		// Modification partielle (PATCH)
		$libellePatch = 'Patched Admin Test ' . uniqid();
		$client->request('PATCH', $iri, [
			'json' => [
				'libelle' => $libellePatch,
			],
			'headers' => ['Content-Type' => 'application/merge-patch+json']
		]);
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas modifier partiellement un état de commande via PATCH.');

		// Vérification de la modification via GET
		$client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas récupérer l\'état de commande après modification partielle.');
		$dataPatch = json_decode($client->getResponse()->getContent(), true);
		$this->assertEquals($libellePatch, $dataPatch['libelle'], 'Le libelle de l\'état de commande modifié partiellement ne correspond pas.');

		// Suppression
		$client->request('DELETE', $iri);
		$this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode(), 'L\'administrateur ne peut pas supprimer un état de commande.');

		// Vérification de la suppression via GET
		$client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode(), 'L\'état de commande n\'a pas été supprimé correctement.');
	}
}
