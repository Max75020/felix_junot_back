<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Utilisateur;


class UtilisateurTest extends TestAuthentificator
{
	/**
	 * Crée un utilisateur avec des données de test et retourne ses informations.
	 *
	 * @param Client $client Le client pour effectuer la requête de création.
	 * @param array $roles Les rôles de l'utilisateur à créer.
	 * @return array Un tableau contenant l'IRI, l'email et le mot de passe de l'utilisateur créé.
	 */
	public function createUtilisateur(Client $client = null, array $roles = ['ROLE_USER']): array
	{
		if (!$client) {
			$client = static::createClient();
		}

		$uniqueEmail = 'user_' . uniqid() . '@example.com';
		$password = 'UserPassword+123';

		$response = $client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Standard',
				'nom' => 'User',
				'email' => $uniqueEmail,
				'telephone' => '0668747201',
				'roles' => $roles,
				'password' => $password,
				'email_valide' => true,
			],
		]);

		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'utilisateur n\'a pas été créé correctement.');
		$data = $response->toArray();
		$this->assertArrayHasKey('@id', $data, 'L\'IRI de l\'utilisateur créé est absente.');

		return [
			'iri' => $data['@id'],
			'email' => $uniqueEmail,
			'password' => $password,
		];
	}

	/**
	 * Crée un client authentifié en tant qu'utilisateur spécifique => Se connecter
	 *
	 * @param string $email    L'email de l'utilisateur.
	 * @param string $password Le mot de passe de l'utilisateur.
	 * @return Client Le client authentifié.
	 */
	private function createAuthenticatedClientAsUser(string $email, string $password): Client
	{
		// Utiliser un client temporaire pour obtenir le token
		$client = static::createClient();
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $email,
				'password' => $password,
			],
		]);

		$this->assertResponseIsSuccessful('Échec de l\'authentification de l\'utilisateur.');
		$data = $response->toArray();
		$this->assertArrayHasKey('token', $data, 'Token JWT non trouvé pour l\'utilisateur.');

		// Créer un nouveau client avec l'option 'auth_bearer'
		return static::createClient([], [
			'auth_bearer' => $data['token'],
		]);
	}

	/**
	 * Teste que la création d'un utilisateur est interdite pour un utilisateur standard.
	 */
	public function testCreateUtilisateurAsUserForbidden(): void
	{
		// Crée un utilisateur standard
		$clientData = $this->createUtilisateur();
		// Crée un client authentifié en tant qu'utilisateur standard
		$client = $this->createAuthenticatedClientAsUser($clientData['email'], $clientData['password']);

		// Tente de créer un utilisateur en tant qu'utilisateur standard
		$responsePost = $client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'New',
				'nom' => 'User',
				'email' => 'new_user_' . uniqid() . '@example.com',
				'telephone' => '0777777777',
				'roles' => ['ROLE_USER'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);

		// Vérifie que l'accès est interdit pour un utilisateur standard
		$this->assertSame(Response::HTTP_FORBIDDEN, $responsePost->getStatusCode(), 'Un utilisateur standard a pu créer un utilisateur.');
	}

	/**
	 * Teste que la création d'un utilisateur est autorisée pour un administrateur.
	 */
	public function testCreateUtilisateurAsAdmin(): void
	{
		// Créer un client authentifié en tant qu'administrateur
		$adminClient = $this->createAuthenticatedClient(true);

		// Créer un nouvel utilisateur en tant qu'administrateur
		$response = $adminClient->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'New',
				'nom' => 'User',
				'email' => 'new_user_' . uniqid() . '@example.com',
				'telephone' => '0777777777',
				'roles' => ['ROLE_USER'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);

		// Vérifier que la réponse est réussie
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Un administrateur n\'a pas pu créer un utilisateur.');

		// Récupérer les données de la réponse
		$data = $response->toArray();
		$this->assertArrayHasKey('@id', $data, 'L\'IRI de l\'utilisateur créé est absente.');
		$this->assertNotEmpty($data['@id'], 'L\'IRI de l\'utilisateur créé est vide.');
	}


	/**
	 * Teste que la création d'un utilisateur est autorisée pour un utilisateur non authentifié.
	 */
	public function testCreateUtilisateurAsAnonymous(): void
	{
		$client = static::createClient(); // Client non authentifié

		// Tente de créer un utilisateur en tant qu'utilisateur non authentifié
		$client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Anonymous',
				'nom' => 'User',
				'email' => 'anonymous_user_' . uniqid() . '@example.com',
				'telephone' => '0777777777',
				'roles' => ['ROLE_USER'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);

		// Vérifie que l'utilisateur a été créé avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Un utilisateur non authentifié n\'a pas pu créer un utilisateur.');
	}

	/**
	 * Teste la récupération de la collection des utilisateurs en tant qu'administrateur.
	 */
	public function testGetCollectionAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$this->createUtilisateur($client);

		$response = $client->request('GET', '/api/utilisateurs');

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de la collection des utilisateurs a échoué.');
		$data = $response->toArray();

		// Vérifie la présence de la clé 'hydra:totalItems'
		$this->assertArrayHasKey('hydra:totalItems', $data, 'La clé hydra:totalItems est absente.');
		$this->assertGreaterThan(0, $data['hydra:totalItems'], 'La collection des utilisateurs est vide.');

		// Vérifie la présence de la clé '@context' et sa valeur
		$this->assertArrayHasKey('@context', $data, 'La clé @context est absente.');
		$this->assertEquals('/api/contexts/Utilisateur', $data['@context'], 'Le contexte API n\'est pas correct.');
	}

	/**
	 * Teste que la récupération de la collection des utilisateurs est interdite pour un utilisateur standard.
	 */
	public function testGetCollectionAsUserForbidden(): void
	{
		$client = $this->createAuthenticatedClient(); // Utilisateur standard

		$client->request('GET', '/api/utilisateurs');

		// Vérifie que l'accès est interdit pour un utilisateur standard
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu récupérer la collection des utilisateurs.');
	}

	/**
	 * Teste la récupération d'un utilisateur spécifique par l'administrateur.
	 */
	public function testGetUtilisateurAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurData = $this->createUtilisateur($client);

		$response = $client->request('GET', $utilisateurData['iri']);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de l\'utilisateur a échoué.');
		$data = $response->toArray();

		// Vérifie que les données de l'utilisateur sont correctes
		$this->assertEquals('Standard', $data['prenom'], 'Le prénom de l\'utilisateur ne correspond pas.');
		$this->assertEquals('User', $data['nom'], 'Le nom de l\'utilisateur ne correspond pas.');
		$this->assertEquals('0668747201', $data['telephone'], 'Le téléphone de l\'utilisateur ne correspond pas.');
		$this->assertContains('ROLE_USER', $data['roles'], 'Le rôle de l\'utilisateur n\'est pas correct.');
		$this->assertTrue($data['email_valide'], 'Le statut email_valide de l\'utilisateur n\'est pas correct.');
	}

	/**
	 * Teste la récupération d'un utilisateur spécifique par le propriétaire du compte.
	 */
	public function testGetOwnUtilisateurAsUser(): void
	{
		// Crée un nouvel utilisateur et récupère ses informations
		$userData = $this->createUtilisateur();
		// Crée un client authentifié en tant que cet utilisateur
		$userClient = $this->createAuthenticatedClientAsUser($userData['email'], $userData['password']);
		$utilisateurIri = $userData['iri'];

		$response = $userClient->request('GET', $utilisateurIri);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de son propre utilisateur a échoué.');
		$data = $response->toArray();

		// Vérifie que les données de l'utilisateur sont correctes
		$this->assertEquals('Standard', $data['prenom'], 'Le prénom de l\'utilisateur ne correspond pas.');
		$this->assertEquals('User', $data['nom'], 'Le nom de l\'utilisateur ne correspond pas.');
		$this->assertEquals('0668747201', $data['telephone'], 'Le téléphone de l\'utilisateur ne correspond pas.');
		$this->assertContains('ROLE_USER', $data['roles'], 'Le rôle de l\'utilisateur n\'est pas correct.');
		$this->assertTrue($data['email_valide'], 'Le statut email_valide de l\'utilisateur n\'est pas correct.');
	}

	/**
	 * Teste la mise à jour d'un utilisateur en tant qu'administrateur.
	 */
	public function testUpdateUtilisateurAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurData = $this->createUtilisateur($client);

		$uniqueEmail = 'updated_' . uniqid() . '@example.com';

		$updatedData = [
			'prenom' => 'UpdateUser',
			'nom' => 'UserUpdatebyAdmin',
			'email' => $uniqueEmail,
			'telephone' => '0777777777',
			'roles' => ['ROLE_USER'],
			'password' => 'UserPassword+123',
			'email_valide' => false,
		];

		$client->request('PUT', $utilisateurData['iri'], [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => $updatedData,
		]);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PUT de l\'utilisateur a échoué.');

		// Vérifie que les données ont été mises à jour correctement
		$this->assertJsonContains(['prenom' => 'UpdateUser'], 'Le prénom de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['nom' => 'UserUpdatebyAdmin'], 'Le nom de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['telephone' => '0777777777'], 'Le téléphone de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['email_valide' => false], 'Le statut email_valide de l\'utilisateur n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la mise à jour d'un utilisateur par le propriétaire du compte.
	 */
	public function testUpdateOwnUtilisateurAsUser(): void
	{
		// Créer un nouvel utilisateur
		$userData = $this->createUtilisateur();

		// Créer un client authentifié en tant que cet utilisateur
		$userClient = $this->createAuthenticatedClientAsUser($userData['email'], $userData['password']);

		// Email de l'utilisateur modifié
		$emailUpdate = 'updated_' . uniqid() . '@example.com';
		// Effectuer la mise à jour avec le client authentifié
		$userClient->request('PUT', $userData['iri'], [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => [
				'prenom' => 'UpdatedUser',
				'nom' => 'UserUpdatebyHimself',
				'email' => $emailUpdate,
				'telephone' => '0888888888',
				'roles' => ['ROLE_USER'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PUT de l\'utilisateur a échoué.');

		// Vérifie que les données ont été mises à jour correctement
		$this->assertJsonContains(['prenom' => 'UpdatedUser'], 'Le prénom de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['nom' => 'UserUpdatebyHimself'], 'Le nom de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['telephone' => '0888888888'], 'Le téléphone de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['email_valide' => true], 'Le statut email_valide de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['email' => $emailUpdate], 'L\'email de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['roles' => ['ROLE_USER']], 'Les rôles de l\'utilisateur ont été modifiés alors qu\'ils ne devraient pas l\'être.');
	}


	/**
	 * Teste la mise à jour d'un utilisateur par un utilisateur standard (autre que le propriétaire).
	 */
	public function testUpdateUtilisateurAsUserForbidden(): void
	{
		// Crée un administrateur pour créer un utilisateur
		$adminClient = $this->createAuthenticatedClient(true);
		// Utilisateur créé par l'administrateur
		$utilisateurData = $this->createUtilisateur($adminClient);

		// Crée un utilisateur standard pour tester la mise à jour
		$userClient = $this->createAuthenticatedClient();
		// Tente de mettre à jour l'utilisateur créé par l'administrateur
		$responseUser = $userClient->request('PUT', $utilisateurData['iri'], [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => [
				'prenom' => 'Forbidden Update',
			],
		]);

		// Vérifie que la mise à jour par un utilisateur standard est interdite en obtenant un 404
		// API Platform retourne un 404 pour ne pas divulguer l'existence de la ressource
		$this->assertSame(
			Response::HTTP_NOT_FOUND,
			$responseUser->getStatusCode(),
			'Un utilisateur standard a pu accéder à un utilisateur qui n\'est pas le sien.'
		);
	}


	/**
	 * Teste la suppression d'un utilisateur en tant qu'administrateur.
	 */
	public function testDeleteUtilisateurAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurData = $this->createUtilisateur($client);

		$client->request('DELETE', $utilisateurData['iri']);
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de l\'utilisateur a échoué.');

		// Vérifie que l'utilisateur n'existe plus
		$client->request('GET', $utilisateurData['iri']);
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'L\'utilisateur n\'a pas été supprimé correctement.');
	}

	/**
	 * Teste la suppression de son propre compte par un utilisateur standard.
	 */
	public function testDeleteOwnUtilisateurAsUser(): void
	{
		// Crée un nouvel utilisateur
		$userData = $this->createUtilisateur();
		// Crée un client authentifié en tant que cet utilisateur
		$userClient = $this->createAuthenticatedClientAsUser($userData['email'], $userData['password']);
		// Supprime son propre compte
		$userClient->request('DELETE', $userData['iri']);

		// Vérifie que la suppression a réussi
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de son propre compte a échoué.');

		// Utilisation de l'administrateur pour vérifier que l'utilisateur n'existe plus
		$adminClient = $this->createAuthenticatedClient(true); // Administrateur
		$adminClient->request('GET', $userData['iri']);

		// Vérifie que l'utilisateur n'existe plus
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'Le compte utilisateur n\'a pas été supprimé correctement.');
	}

	/**
	 * Teste la suppression d'un utilisateur par un utilisateur standard (autre que le propriétaire).
	 */
	public function testDeleteUtilisateurAsUserForbidden(): void
	{
		// Crée un client administrateur pour créer un utilisateur cible
		$adminClient = $this->createAuthenticatedClient(true);

		// Crée un utilisateur via le client administrateur et récupère ses données
		$utilisateurData = $this->createUtilisateur($adminClient);

		// Crée un client utilisateur standard pour tenter de supprimer l'utilisateur cible
		$userClient = $this->createAuthenticatedClient();

		// Tente de supprimer l'utilisateur cible avec le client utilisateur standard
		$responseDelete = $userClient->request('DELETE', $utilisateurData['iri']);

		// Vérifie que la suppression par un utilisateur standard est interdite en obtenant un 404
		// API Platform retourne un 404 pour ne pas divulguer l'existence de la ressource
		$this->assertSame(
			Response::HTTP_NOT_FOUND,
			$responseDelete->getStatusCode(),
			'Un utilisateur standard a pu supprimer un utilisateur qui n\'est pas le sien.'
		);

		// Optionnel : Vérifier le contenu de la réponse pour s'assurer qu'il correspond aux attentes
		$data = $responseDelete->toArray(false);
		// API Platform peut retourner un message générique pour un 404, par exemple :
		// {
		//     "hydra:description": "Not Found",
		//     "hydra:title": "An error occurred"
		// }
		$this->assertArrayHasKey('hydra:description', $data, 'La réponse ne contient pas la clé hydra:description.');
		$this->assertSame('Not Found', $data['hydra:description'], 'Le message de la réponse n\'est pas "Not Found".');
	}


	/**
	 * Teste la création d'un utilisateur avec un email dupliqué.
	 */
	public function testCreateUtilisateurDuplicateEmail(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$uniqueEmail = 'duplicate_' . uniqid() . '@example.com';

		// Crée un premier utilisateur avec un email unique
		$client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'First',
				'nom' => 'User',
				'email' => $uniqueEmail,
				'telephone' => '0668747201',
				'roles' => ['ROLE_USER'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le premier utilisateur n\'a pas été créé correctement.');

		// Tente de créer un deuxième utilisateur avec le même email
		$client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Second',
				'nom' => 'User',
				'email' => $uniqueEmail, // Email dupliqué
				'telephone' => '0777777777',
				'roles' => ['ROLE_USER'],
				'password' => 'AnotherPassword+123',
				'email_valide' => true,
			],
		]);
		$this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY, 'La création d\'un utilisateur avec un email dupliqué a réussi alors qu\'elle aurait dû échouer.');
	}

	/**
	 * Teste la génération d'un token de réinitialisation pour un utilisateur.
	 */
	public function testGenerateResetToken(): void
	{
		// Créer un utilisateur de test
		$userData = $this->createUtilisateur();

		// Créer un client (pas besoin d'être authentifié pour cette opération)
		$client = static::createClient();

		// Effectuer la requête pour générer le token
		$client->request('POST', '/api/password-reset-request', [
			'json' => [
				'email' => $userData['email'],
			],
		]);

		// Vérifier que la réponse est correcte (HTTP 200)
		$this->assertResponseIsSuccessful('La génération du token de réinitialisation a échoué.');

		// Optionnel : Vérifier le message de la réponse
		$this->assertJsonContains(['message' => 'Si l\'email existe, un lien de réinitialisation a été envoyé.']);

		// Récupérer l'utilisateur en base de données
		/** @var EntityManagerInterface $entityManager */
		$entityManager = static::getContainer()->get('doctrine')->getManager();
		/** @var UtilisateurRepository $userRepository */
		$userRepository = $entityManager->getRepository(Utilisateur::class);
		/** @var Utilisateur $user */
		$user = $userRepository->findOneBy(['email' => $userData['email']]);

		// Vérifier que le token est bien généré
		$this->assertNotNull($user->getTokenReinitialisation(), 'Le token de réinitialisation n\'a pas été généré en base de données.');
	}


	/**
	 * Teste l'utilisation d'un token de réinitialisation pour changer le mot de passe d'un utilisateur.
	 */
	public function testResetPassword(): void
	{
		// Créer un utilisateur de test
		$userData = $this->createUtilisateur();

		// Générer le token de réinitialisation
		$client = static::createClient();
		$client->request('POST', '/api/password-reset-request', [
			'json' => [
				'email' => $userData['email'],
			],
		]);

		// Vérifier que la génération du token a réussi
		$this->assertResponseIsSuccessful('La génération du token de réinitialisation a échoué.');

		// Récupérer l'utilisateur en base de données
		/** @var EntityManagerInterface $entityManager */
		$entityManager = static::getContainer()->get('doctrine')->getManager();
		/** @var UtilisateurRepository $userRepository */
		$userRepository = $entityManager->getRepository(Utilisateur::class);
		/** @var Utilisateur $user */
		$user = $userRepository->findOneBy(['email' => $userData['email']]);

		// Récupérer le token
		$token = $user->getTokenReinitialisation();
		$this->assertNotNull($token, 'Le token de réinitialisation n\'a pas été généré.');

		// Effectuer la requête pour réinitialiser le mot de passe
		$newPassword = 'NewPassword+123';
		$client->request('POST', '/api/password-reset', [
			'json' => [
				'email' => $userData['email'],
				'token' => $token,
				'new_password' => $newPassword,
			],
		]);

		// Vérifier que la réinitialisation a réussi
		$this->assertResponseIsSuccessful('La réinitialisation du mot de passe a échoué.');

		// Optionnel : Vérifier le message de la réponse
		$this->assertJsonContains(['message' => 'Mot de passe réinitialisé avec succès.']);

		// Tenter de se connecter avec le nouveau mot de passe
		$authenticatedClient = $this->createAuthenticatedClientAsUser($userData['email'], $newPassword);

		// Vérifier que la connexion est réussie
		$this->assertNotNull($authenticatedClient, 'La connexion avec le nouveau mot de passe a échoué.');
	}


	/**
	 * Teste la mise à jour partielle d'un utilisateur par le propriétaire du compte.
	 */
	public function testPatchOwnUtilisateurAsUser(): void
	{

		// Crée un nouvel utilisateur et récupère ses informations
		$userData = $this->createUtilisateur();
		// Crée un client authentifié en tant que cet utilisateur
		$userClient = $this->createAuthenticatedClientAsUser($userData['email'], $userData['password']);
		// Récupère l'IRI de l'utilisateur
		$ownUtilisateurIri = $userData['iri'];

		$userClient->request('PATCH', $ownUtilisateurIri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			],
			'json' => [
				'prenom' => 'PatchedName',
			],
		]);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PATCH de l\'utilisateur a échoué.');

		// Vérifie que les données ont été mises à jour correctement
		$this->assertJsonContains(['prenom' => 'PatchedName'], 'Le prénom de l\'utilisateur n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la mise à jour partielle d'un utilisateur par un utilisateur standard (autre que le propriétaire).
	 */
	public function testPatchUtilisateurAsUserForbidden(): void
	{
		// Crée un client administrateur pour créer un utilisateur cible
		$adminClient = $this->createAuthenticatedClient(true);

		// Crée un utilisateur via le client administrateur et récupère ses données
		$utilisateurData = $this->createUtilisateur($adminClient);

		// Crée un client utilisateur standard pour tenter de patcher l'utilisateur cible
		$userClient = $this->createAuthenticatedClient();

		// Tente de patcher l'utilisateur cible avec le client utilisateur standard
		$responsePatch = $userClient->request('PATCH', $utilisateurData['iri'], [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			],
			'json' => [
				'prenom' => 'ForbiddenPatch',
			],
		]);

		// Vérifie que la mise à jour partielle par un utilisateur standard est interdite en obtenant un 404
		// API Platform retourne un 404 pour ne pas divulguer l'existence de la ressource
		$this->assertSame(
			Response::HTTP_NOT_FOUND,
			$responsePatch->getStatusCode(),
			'Un utilisateur standard a pu patcher un utilisateur qui n\'est pas le sien.'
		);

		// Optionnel : Vérifier le contenu de la réponse pour s'assurer qu'il correspond aux attentes
		$data = $responsePatch->toArray(false);

		// API Platform peut retourner un message générique pour un 404, par exemple :
		// {
		//     "hydra:description": "Not Found",
		//     "hydra:title": "An error occurred"
		// }
		$this->assertArrayHasKey('hydra:description', $data, 'La réponse ne contient pas la clé hydra:description.');
		$this->assertSame('Not Found', $data['hydra:description'], 'Le message de la réponse n\'est pas "Not Found".');
	}
}
/*
 * Pour exécuter les tests, exécutez la commande suivante :
 * $ ./bin/phpunit tests/Functional/UtilisateurTest.php
 * Dans le conteneur Symfony de Docker
 *  php bin/phpunit tests/Functional/UtilisateurTest.php
 */