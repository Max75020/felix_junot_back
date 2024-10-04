<?php

namespace App\Tests\Functional;

use App\Tests\Authentificator\TestAuthentificator;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Functional\UtilisateurTest;
use App\Tests\Functional\EtatCommandeTest;
use ApiPlatform\Symfony\Bundle\Test\Client;

class CommandeTest extends TestAuthentificator
{

	public function createCommandeTest(Client $client = null): string
	{
		if ($client == null) {
			$client = $this->createAdminClient();
			// Créer un utilisateur pour la commande
			$utilisateurTest = new UtilisateurTest();
			// Créer un utilisateur basique
			$utilisateurBasique = $utilisateurTest->createUtilisateur($client);
			// Réccupérer l'IRI de l'utilisateur
			$utilisateurIri = $utilisateurBasique['iri'];
		} else {
			$utilisateurIri = $this->getUserIri($client);
		}

		// Créer un etat commande
		$etatCommandeTest = new EtatCommandeTest();
		// Créer un etat commande basique
		$etatCommandeIri = $etatCommandeTest->createEtatCommandeTest();

		// Nom du transporteur
		$transporteur = 'Colissimo';

		// Numéro de suivi aléatoire
		$numeroSuivi = uniqid();

		$client->request('POST', '/api/commandes', [
			'json' => [
				"utilisateur" => $utilisateurIri,
				"total" => "19.99",
				"etat_commande" => $etatCommandeIri,
				"transporteur" => $transporteur,
				"poids" => "1.2",
				"frais_livraison" => "4.95",
				"numero_suivi" => $numeroSuivi
			]
		]);
		// Vérifier le statut de la réponse
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		// Retourne l'IRI de la commande
		return $client->getResponse()->toArray()['@id'];
	}

	public function createAdminClient()
	{
		return $this->createAuthenticatedClient(true);
	}

	public function createUserClient()
	{
		return $this->createAuthenticatedClient();
	}

	/**
	 * Test que l'utilisateur peut récupérer sa collection de commandes.
	 */
	public function testUserCanGetCommandeCollection()
	{
		// Créer un utilisateur A unique
		$userA = $this->createUniqueUser();
		$userAClient = $userA['client'];
		$userAIri = $userA['iri'];
		$userAId = $userA['id'];

		// Créer deux commandes pour l'utilisateur A
		$commandeA1 = $this->createCommandeTest($userAClient);
		$commandeA2 = $this->createCommandeTest($userAClient);

		// Créer un utilisateur B unique
		$userB = $this->createUniqueUser();
		$userBClient = $userB['client'];
		$userBIri = $userB['iri'];
		$userBId = $userB['id'];

		// Créer une commande pour l'utilisateur B
		$commandeB1 = $this->createCommandeTest($userBClient);

		// Vérifier que les utilisateurs et les commandes sont différents
		$this->assertNotEmpty($userA['iri'], 'IRI de l\'utilisateur A est vide.');
		$this->assertNotEmpty($userB['iri'], 'IRI de l\'utilisateur B est vide.');

		$this->assertNotEmpty($userA['id'], 'ID de l\'utilisateur A est vide.');
		$this->assertNotEmpty($userB['id'], 'ID de l\'utilisateur B est vide.');

		$this->assertNotEquals($userA['id'], $userB['id'], 'Les utilisateurs A et B ont le même ID.');
		$this->assertNotEquals($userA['iri'], $userB['iri'], 'Les utilisateurs A et B ont le même IRI.');

		$this->assertNotEquals($userA['iri'], $userB['iri'], 'Les utilisateurs A et B ont le même IRI.');
		$this->assertNotEquals($userA['email'], $userB['email'], 'Les utilisateurs A et B ont le même email.');


		// L'utilisateur A récupère la collection de commandes
		$responseGetCollection = $userAClient->request('GET', '/api/commandes', [], [], [
			'HTTP_ACCEPT' => 'application/json',
		]);

		// Vérifier que la réponse est OK
		$this->assertEquals(Response::HTTP_OK, $responseGetCollection->getStatusCode(), 'L\'utilisateur standard ne peut pas récupérer toutes ses commandes.');

		// Récupérer les données de la réponse
		$responseData = $userAClient->getResponse()->toArray();

		// Vérifier que seules les commandes de l'utilisateur A sont présentes
		foreach ($responseData['hydra:member'] as $commande) {
			$this->assertEquals(
				$userAIri,
				$commande['utilisateur'],
				'La commande ne appartient pas à l\'utilisateur connecté.'
			);
		}

		// Vérifier le nombre de commandes retournées (2 commandes pour l'utilisateur A)
		$this->assertCount(
			2,
			$responseData['hydra:member'],
			'L\'utilisateur voit des commandes qui ne lui appartiennent pas ou pas le nombre attendu.'
		);
	}


	public function testAdminCanPerformCrudOperations()
	{
		$client = $this->createAdminClient();
		$iri = $this->createCommandeTest();

		// GET specific
		$responseGet = $client->request('GET', $iri);
		$this->assertEquals(Response::HTTP_OK, $responseGet->getStatusCode(), 'L\'administrateur ne peut pas récupérer une commande spécifique.');

		// GET collection
		$responseGetCollection = $client->request('GET', '/api/commandes');
		$this->assertEquals(Response::HTTP_OK, $responseGetCollection->getStatusCode(), 'L\'administrateur ne peut pas récupérer toutes les commandes.');

		// PATCH
		$responsePatch = $client->request('PATCH', $iri, [
			'json' => [
				"total" => "10.10"
			],
			'headers' => ['Content-Type' => 'application/merge-patch+json']
		]);
		// Vérifier le statut de la réponse
		$this->assertEquals(Response::HTTP_OK, $responsePatch->getStatusCode(), 'L\'administrateur ne peut pas mettre à jour une commande.');

		// DELETE
		$responseDelete = $client->request('DELETE', $iri);
		// Vérifier le statut de la réponse
		$this->assertEquals(Response::HTTP_NO_CONTENT, $responseDelete->getStatusCode(), 'L\'administrateur ne peut pas supprimer une commande.');
	}
}
