<?php

namespace App\Tests\Functional;

use App\Tests\Authentificator\TestAuthentificator;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Functional\CommandeTest;
use App\Tests\Functional\EtatCommandeTest;

class HistoriqueEtatCommandeTest extends TestAuthentificator
{

	//Vérifie que lorsque l'utilisateur crée une commande, 
	//l'historique d'état de commande est bien mis à jour et enregistré dans la base de données.
	public function testCreateCommandeAndCheckHistoriqueEtatCommande()
	{
		// Créer un client authentifié en tant qu'utilisateur
		$user = $this->createAuthenticatedClient();

		// Instancier une commande de test
		$commandeTest = new CommandeTest();

		// Créer une commande et récupérer la réponse complète
		$responseCreateCommande = $commandeTest->createCommandeWithFullResponse($user);

		// Extraire l'ID de la commande à partir de la réponse
		$idCommande = $responseCreateCommande['id_commande'];

		// Récupérer l'historique de la commande via la route personnalisée en utilisant l'ID
		$responseGetHistorique = $user->request('GET', "/api/commandes/{$idCommande}/historique_etat_commandes");

		// Vérifier le statut de la réponse (200 OK)
		$this->assertEquals(Response::HTTP_OK, $responseGetHistorique->getStatusCode(), "La requête GET sur l'historique d'état de commande a échoué.");

		// Vérifier qu'au moins un historique d'état de commande a été créé pour la commande
		$historiqueData = $responseGetHistorique->toArray();
		$this->assertNotEmpty($historiqueData, "L'historique d'état de commande est vide alors qu'il devrait être mis à jour.");
	}


	//Vérifie qu'un utilisateur standard n'a pas les permissions pour créer un historique d'état de commande.
	// Ce scénario doit échouer avec une erreur 403 Forbidden.
	public function testUserCannotCreateHistoriqueEtatCommande()
	{
		// Créer un client authentifié en tant qu'utilisateur standard
		$user = $this->createAuthenticatedClient(false);

		// Instancier une commande de test
		$commandeTest = new CommandeTest();

		// Créer une commande pour l'utilisateur et récupérer la réponse complète
		$responseCreateCommande = $commandeTest->createCommandeWithFullResponse($user);
		$idCommande = $responseCreateCommande['id_commande'];

		// Date de l'historique d'état de commande aujourd'hui au format ISO 8601
		$dateEtat = date('c'); // 'c' correspond au format ISO 8601

		// L'utilisateur essaie de créer un historique d'état de commande via la sous-ressource (cela doit échouer avec un 403 Forbidden)
		$responseCreateHistory = $user->request('POST', "/api/commandes/{$idCommande}/historique_etat_commandes", [
			'json' => [
				'etat_commande' => '/api/etats_commande/1', // Assurez-vous que cet IRI existe dans votre base de données
				'date_etat' => $dateEtat
			]
		]);

		// Vérifier que l'utilisateur n'a pas l'autorisation de créer un historique d'état (403 Forbidden)
		$this->assertEquals(Response::HTTP_FORBIDDEN, $responseCreateHistory->getStatusCode(), "Un utilisateur standard a réussi à créer un historique d'état de commande alors qu'il ne devrait pas.");
	}

	// Vérifie qu'un administrateur peut créer un historique d'état de commande, puis le récupérer via l'API.
	public function testAdminCreatesAndGetsHistoriqueEtatCommande()
	{
		// Créer un client authentifié en tant qu'administrateur
		$admin = $this->createAuthenticatedClient(true);

		// Instancier une commande de test
		$commandeTest = new CommandeTest();

		// Créer une commande et récupérer la réponse complète
		$responseCreateCommande = $commandeTest->createCommandeWithFullResponse($admin);
		$idCommande = $responseCreateCommande['id_commande'];

		// Instancier et créer un état de commande de test
		$etatCommandeTest = new EtatCommandeTest();
		$iriEtatCommande = $etatCommandeTest->createEtatCommandeTest();

		// Date de l'historique d'état de commande aujourd'hui au format ISO 8601
		$dateEtat = date('c'); // 'c' correspond au format ISO 8601

		// Créer un historique d'état de commande via la sous-ressource
		$responseCreateHistory = $admin->request('POST', "/api/commandes/{$idCommande}/historique_etat_commandes", [
			'json' => [
				'etat_commande' => $iriEtatCommande,
				'date_etat' => $dateEtat
			]
		]);

		// Vérifier que la création de l'historique est réussie (201 Created)
		$this->assertEquals(
			Response::HTTP_CREATED,
			$responseCreateHistory->getStatusCode(),
			"La création de l'historique d'état de commande par un administrateur a échoué."
		);

		// Récupérer l'historique d'état de la commande créée via la route personnalisée
		$responseGetHistory = $admin->request('GET', "/api/commandes/{$idCommande}/historique_etat_commandes");
		$this->assertEquals(
			Response::HTTP_OK,
			$responseGetHistory->getStatusCode(),
			"La requête GET sur l'historique d'état de commande a échoué pour l'administrateur."
		);

		// Convertir la réponse en tableau
		$historiqueData = $responseGetHistory->toArray();

		// Vérifier que la clé 'hydra:member' existe
		$this->assertArrayHasKey('hydra:member', $historiqueData, "La réponse ne contient pas la clé 'hydra:member'.");

		// Vérifier qu'il y a au moins un historique créé
		$this->assertNotEmpty(
			$historiqueData['hydra:member'],
			"Aucun historique d'état trouvé pour cette commande alors qu'un historique a été créé."
		);

		// Récupérer le premier élément de l'historique
		$historiqueItem = $historiqueData['hydra:member'][0];

		// Vérifier que l'historique d'état de commande contient l'état créé
		$this->assertEquals(
			$dateEtat,
			$historiqueItem['date_etat'],
			"La date de l'historique d'état ne correspond pas à celle envoyée."
		);
		$this->assertEquals(
			$iriEtatCommande,
			$historiqueItem['etat_commande'],
			"L'état de commande de l'historique ne correspond pas à celui envoyé."
		);
	}

	// Vérifie qu'un administrateur peut changer l'état d'une commande et que l'historique est mis à jour avec la date du changement.
	public function testAdminCanChangeEtatCommandeAndHistoriqueIsUpdated()
	{
		// Créer un client authentifié en tant qu'administrateur
		$admin = $this->createAuthenticatedClient(true);

		// Instancier une commande de test
		$commandeTest = new CommandeTest();

		// Créer une commande et récupérer la réponse complète
		$responseCreateCommande = $commandeTest->createCommandeWithFullResponse($admin);
		$idCommande = $responseCreateCommande['id_commande'];

		// Instancier EtatCommandeTest pour pouvoir créer un nouvel état de commande
		$etatCommandeTest = new EtatCommandeTest();

		// Créer un nouvel état de commande pour le changement
		$iriEtatCommandeNouveau = $etatCommandeTest->createEtatCommandeTest();

		// Changer l'état de la commande
		$admin->request('PATCH', "/api/commandes/{$idCommande}", [
			'json' => [
				'etat_commande' => $iriEtatCommandeNouveau
			],
			'headers' => ['Content-Type' => 'application/merge-patch+json']
		]);
		$this->assertEquals(
			Response::HTTP_OK,
			$admin->getResponse()->getStatusCode(),
			"L'administrateur ne peut pas changer l'état de la commande."
		);

		// Récupérer l'historique de la commande
		$responseGetHistory = $admin->request('GET', "/api/commandes/{$idCommande}/historique_etat_commandes");
		$this->assertEquals(
			Response::HTTP_OK,
			$responseGetHistory->getStatusCode(),
			"La requête GET sur l'historique d'état de commande a échoué pour l'administrateur."
		);

		// Convertir la réponse en tableau
		$historiqueData = $responseGetHistory->toArray();

		// Vérifier qu'il y a deux historiques (création et changement d'état)
		$this->assertCount(
			2,
			$historiqueData['hydra:member'],
			"Le nombre d'historiques d'état de commande n'est pas correct."
		);

		// Vérifier le dernier historique pour s'assurer qu'il correspond au changement
		$dernierHistorique = end($historiqueData['hydra:member']);
		$this->assertEquals(
			$iriEtatCommandeNouveau,
			$dernierHistorique['etat_commande'],
			"L'état de commande dans l'historique ne correspond pas à celui envoyé."
		);
		$this->assertNotNull(
			$dernierHistorique['date_etat'],
			"La date de l'historique d'état de commande est nulle."
		);

		// Vérifier que la date est bien une date valide (format ISO 8601)
		$dateValide = \DateTime::createFromFormat(\DateTime::ATOM, $dernierHistorique['date_etat']);
		$this->assertInstanceOf(
			\DateTimeInterface::class,
			$dateValide,
			"La date de l'historique d'état de commande n'est pas une date valide."
		);
	}
}
