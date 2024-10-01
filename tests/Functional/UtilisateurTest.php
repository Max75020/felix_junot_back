<?php

namespace App\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use App\Tests\Authentificator\TestAuthentificator;

class UtilisateurTest extends TestAuthentificator
{
	/**
	 * Crée un utilisateur en tant qu'administrateur avec des données de test.
	 *
	 * @param \ApiPlatform\Symfony\Bundle\Test\Client $client Le client administrateur authentifié.
	 * @return string L'Iri de l'utilisateur créé.
	 */
	private function createUtilisateurAsAdmin($client): string
	{
		$uniqueEmail = 'admin_user_' . uniqid() . '@example.com';

		$response = $client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Admin',
				'nom' => 'User',
				'email' => $uniqueEmail,
				'telephone' => '0668747201',
				'roles' => ['ROLE_ADMIN'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);

		// Vérifie que l'utilisateur a été créé avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'utilisateur n\'a pas été créé correctement.');
		$data = $response->toArray();
		$this->assertArrayHasKey('@id', $data, 'L\'IRI de l\'utilisateur créé est absente.');

		return $data['@id'];
	}

	/**
	 * Crée un utilisateur en tant qu'utilisateur standard avec des données de test.
	 *
	 * @param \ApiPlatform\Symfony\Bundle\Test\Client $client Le client standard authentifié.
	 * @return string L'Iri de l'utilisateur créé.
	 */
	private function createUtilisateurAsUser($client): string
	{
		$uniqueEmail = 'user_' . uniqid() . '@example.com';

		$response = $client->request('POST', '/api/utilisateurs', [
			'json' => [
				'prenom' => 'Standard',
				'nom' => 'User',
				'email' => $uniqueEmail,
				'telephone' => '0668747201',
				'roles' => ['ROLE_USER'],
				'password' => 'UserPassword+123',
				'email_valide' => true,
			],
		]);

		// Vérifie que l'utilisateur a été créé avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'utilisateur n\'a pas été créé correctement.');
		$data = $response->toArray();
		$this->assertArrayHasKey('@id', $data, 'L\'IRI de l\'utilisateur créé est absente.');

		return $data['@id'];
	}

	/**
	 * Teste la création d'un utilisateur en tant qu'administrateur.
	 */
	public function testCreateUtilisateurAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur

		$utilisateurIri = $this->createUtilisateurAsAdmin($client);

		$this->assertNotEmpty($utilisateurIri, 'L\'IRI de l\'utilisateur créé est vide.');
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le statut HTTP n\'est pas 201 Created.');
	}

	/**
	 * Teste la création d'un utilisateur en tant qu'utilisateur standard.
	 */
	public function testCreateUtilisateurAsUser(): void
	{
		$client = $this->createAuthenticatedClient(); // Utilisateur standard

		$utilisateurIri = $this->createUtilisateurAsUser($client);

		$this->assertNotEmpty($utilisateurIri, 'L\'IRI de l\'utilisateur créé est vide.');
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Le statut HTTP n\'est pas 201 Created.');
	}

	/**
	 * Teste la récupération de la collection des utilisateurs en tant qu'administrateur.
	 */
	public function testGetCollectionAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$this->createUtilisateurAsAdmin($client);

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
		$utilisateurIri = $this->createUtilisateurAsAdmin($client);

		$response = $client->request('GET', $utilisateurIri);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La récupération de l\'utilisateur a échoué.');
		$data = $response->toArray();

		// Vérifie que les données de l'utilisateur sont correctes
		$this->assertEquals('Admin', $data['prenom'], 'Le prénom de l\'utilisateur ne correspond pas.');
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
		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$ownUtilisateurIri = $this->createUtilisateurAsUser($userClient);

		$response = $userClient->request('GET', $ownUtilisateurIri);

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
		$utilisateurIri = $this->createUtilisateurAsAdmin($client);

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

		$client->request('PUT', $utilisateurIri, [
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
		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$ownUtilisateurIri = $this->createUtilisateurAsUser($userClient);

		$updatedData = [
			'prenom' => 'UpdatedStandard',
			'nom' => 'User',
			'telephone' => '0888888888',
			'email_valide' => true,
			// Ajoutez d'autres champs si nécessaire
		];

		$userClient->request('PUT', $ownUtilisateurIri, [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => $updatedData,
		]);

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La mise à jour PUT de l\'utilisateur a échoué.');

		// Vérifie que les données ont été mises à jour correctement
		$this->assertJsonContains(['prenom' => 'UpdatedStandard'], 'Le prénom de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['nom' => 'User'], 'Le nom de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['telephone' => '0888888888'], 'Le téléphone de l\'utilisateur n\'a pas été mis à jour correctement.');
		$this->assertJsonContains(['email_valide' => true], 'Le statut email_valide de l\'utilisateur n\'a pas été mis à jour correctement.');
	}

	/**
	 * Teste la mise à jour d'un utilisateur par un utilisateur standard (autre que le propriétaire).
	 */
	public function testUpdateUtilisateurAsUserForbidden(): void
	{
		$adminClient = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurIri = $this->createUtilisateurAsAdmin($adminClient);

		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$userClient->request('PUT', $utilisateurIri, [
			'headers' => [
				'Content-Type' => 'application/ld+json',
			],
			'json' => [
				'prenom' => 'Forbidden Update',
			],
		]);

		// Vérifie que la mise à jour par un utilisateur standard est interdite
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu mettre à jour un utilisateur qui n\'est pas le sien.');
	}

	/**
	 * Teste la suppression d'un utilisateur en tant qu'administrateur.
	 */
	public function testDeleteUtilisateurAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurIri = $this->createUtilisateurAsAdmin($client);

		$client->request('DELETE', $utilisateurIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de l\'utilisateur a échoué.');

		// Vérifie que l'utilisateur n'existe plus
		$client->request('GET', $utilisateurIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'L\'utilisateur n\'a pas été supprimé correctement.');
	}

	/**
	 * Teste la suppression de son propre compte par un utilisateur standard.
	 */
	public function testDeleteOwnUtilisateurAsUser(): void
	{
		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$ownUtilisateurIri = $this->createUtilisateurAsUser($userClient);

		$userClient->request('DELETE', $ownUtilisateurIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT, 'La suppression de son propre compte a échoué.');

		// Vérifie que l'utilisateur n'existe plus
		$userClient->request('GET', $ownUtilisateurIri);
		$this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'Le compte utilisateur n\'a pas été supprimé correctement.');
	}

	/**
	 * Teste la suppression d'un utilisateur par un utilisateur standard (autre que le propriétaire).
	 */
	public function testDeleteUtilisateurAsUserForbidden(): void
	{
		$adminClient = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurIri = $this->createUtilisateurAsAdmin($adminClient);

		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$userClient->request('DELETE', $utilisateurIri);

		// Vérifie que la suppression par un utilisateur standard est interdite
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu supprimer un utilisateur qui n\'est pas le sien.');
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
	public function testGenerateResetTokenAsAdmin(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurIri = $this->createUtilisateurAsAdmin($client);

		$client->request('POST', $utilisateurIri . '/generate-reset-token');

		// Vérifie que la réponse est réussie
		$this->assertResponseIsSuccessful('La génération du token de réinitialisation a échoué.');

		$data = $client->getResponse()->toArray();

		// Vérifie que le token de réinitialisation est présent
		$this->assertArrayHasKey('token_reinitialisation', $data, 'Le token de réinitialisation est absent.');
		$this->assertNotEmpty($data['token_reinitialisation'], 'Le token de réinitialisation est vide.');
	}

	/**
	 * Teste l'utilisation d'un token de réinitialisation pour changer le mot de passe d'un utilisateur.
	 */
	public function testResetPassword(): void
	{
		$client = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurIri = $this->createUtilisateurAsAdmin($client);

		// Génère un token de réinitialisation
		$client->request('POST', $utilisateurIri . '/generate-reset-token');
		$this->assertResponseIsSuccessful('La génération du token de réinitialisation a échoué.');

		$data = $client->getResponse()->toArray();
		$token = $data['token_reinitialisation'] ?? null;

		$this->assertNotEmpty($token, 'Le token de réinitialisation est vide.');

		// Effectue une requête pour réinitialiser le mot de passe
		$client->request('POST', '/api/utilisateurs/reset-password', [
			'json' => [
				'token' => $token,
				'nouveau_password' => 'NewPassword+123',
			],
		]);

		// Vérifie que la réinitialisation a réussi
		$this->assertResponseIsSuccessful('La réinitialisation du mot de passe a échoué.');

		// Optionnel : Vérifie que le mot de passe a été mis à jour en tentant de se connecter avec le nouveau mot de passe
		// Cette étape dépend de la façon dont votre API gère l'authentification
	}

	/**
	 * Teste la mise à jour partielle d'un utilisateur par le propriétaire du compte.
	 */
	public function testPatchOwnUtilisateurAsUser(): void
	{
		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$ownUtilisateurIri = $this->createUtilisateurAsUser($userClient);

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
		$adminClient = $this->createAuthenticatedClient(true); // Administrateur
		$utilisateurIri = $this->createUtilisateurAsAdmin($adminClient);

		$userClient = $this->createAuthenticatedClient(); // Utilisateur standard
		$userClient->request('PATCH', $utilisateurIri, [
			'headers' => [
				'Content-Type' => 'application/merge-patch+json',
			],
			'json' => [
				'prenom' => 'ForbiddenPatch',
			],
		]);

		// Vérifie que la mise à jour par un utilisateur standard est interdite
		$this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, 'Un utilisateur standard a pu patcher un utilisateur qui n\'est pas le sien.');
	}
}
