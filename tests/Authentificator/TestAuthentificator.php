<?php

namespace App\Tests\Authentificator;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Utilisateur;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Tests\Functional\UtilisateurTest;

abstract class TestAuthentificator extends ApiTestCase
{
	private ?string $jwtTokenAdmin = null;
	private ?string $jwtTokenUser = null;

	// Emails fixes pour l'administrateur et l'utilisateur standard
	private string $adminEmail = 'admin@example.com';
	private string $adminPassword = 'AdminPassword+123'; // Au moins 12 caractères
	private string $userEmail = 'user@example.com';
	private string $userPassword = 'UserPassword+123'; // Au moins 12 caractères

	/**
	 * Vérifie et crée un administrateur de test si nécessaire.
	 */
	protected function ensureAdminExists(): void
	{
		/** @var EntityManagerInterface */
		$entityManager = self::getContainer()->get('doctrine')->getManager();

		$admin = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $this->adminEmail]);

		if (!$admin) {
			// Créer l'administrateur directement via l'EntityManager
			$admin = new Utilisateur();
			$admin->setPrenom('Super');
			$admin->setNom('Admin');
			$admin->setEmail($this->adminEmail);
			$admin->setTelephone('0668747201');
			$admin->setRoles(["ROLE_ADMIN"]);
			$admin->setEmailValide(true);

			// Encoder le mot de passe
			/** @var UserPasswordHasherInterface */
			$passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
			$hashedPassword = $passwordHasher->hashPassword($admin, $this->adminPassword);
			$admin->setPassword($hashedPassword);

			$entityManager->persist($admin);
			$entityManager->flush();
		} else {
			// Vérifier que l'administrateur a bien le rôle ROLE_ADMIN
			$roles = $admin->getRoles();
			$this->assertContains('ROLE_ADMIN', $roles, 'L\'administrateur n\'a pas le rôle ROLE_ADMIN.');
		}
	}

	/**
	 * Obtient le jeton JWT de l'administrateur de test.
	 *
	 * @return string Le jeton JWT de l'administrateur de test.
	 */
	protected function getTokenAdmin(): string
	{
		if ($this->jwtTokenAdmin) {
			return $this->jwtTokenAdmin;
		}

		// Assurer que l'administrateur existe
		$this->ensureAdminExists();

		// Authentification pour obtenir le token JWT
		$client = static::createClient();
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $this->adminEmail,
				'password' => $this->adminPassword,
			],
		]);

		if ($response->getStatusCode() !== Response::HTTP_OK) {
			throw new \Exception('Échec de l\'authentification de l\'administrateur.');
		}

		$data = $response->toArray();
		if (!isset($data['token'])) {
			throw new \Exception('Token JWT non trouvé pour l\'administrateur.');
		}

		$this->jwtTokenAdmin = $data['token'];
		return $this->jwtTokenAdmin;
	}

	/**
	 * Vérifie et crée l'utilisateur standard de test si nécessaire.
	 */
	protected function ensureUserExists(): void
	{
		/** @var EntityManagerInterface */
		$entityManager = self::getContainer()->get('doctrine')->getManager();

		$user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $this->userEmail]);

		if (!$user) {
			// Créer l'utilisateur standard directement via l'EntityManager
			$user = new Utilisateur();
			$user->setPrenom('Test');
			$user->setNom('User');
			$user->setEmail($this->userEmail);
			$user->setTelephone('0668747201');
			$user->setRoles(["ROLE_USER"]);
			$user->setEmailValide(true);

			// Encoder le mot de passe
			/** @var UserPasswordHasherInterface */
			$passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
			$hashedPassword = $passwordHasher->hashPassword($user, $this->userPassword);
			$user->setPassword($hashedPassword);

			$entityManager->persist($user);
			$entityManager->flush();
		} else {
			// Vérifier que l'utilisateur a bien le rôle ROLE_USER
			$roles = $user->getRoles();
			$this->assertContains('ROLE_USER', $roles, 'L\'utilisateur n\'a pas le rôle ROLE_USER.');
		}
	}

	/**
	 * Obtient le jeton JWT de l'utilisateur standard.
	 *
	 * @return string Le jeton JWT de l'utilisateur de test.
	 */
	protected function getTokenUser(): string
	{
		if ($this->jwtTokenUser) {
			return $this->jwtTokenUser;
		}

		// Assurer que l'utilisateur standard existe
		$this->ensureUserExists();

		// Authentification pour obtenir le token JWT
		$client = static::createClient();
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $this->userEmail,
				'password' => $this->userPassword,
			],
		]);

		if ($response->getStatusCode() !== Response::HTTP_OK) {
			throw new \Exception('Échec de l\'authentification de l\'utilisateur standard.');
		}

		$data = $response->toArray();
		if (!isset($data['token'])) {
			throw new \Exception('Token JWT non trouvé pour l\'utilisateur standard.');
		}

		$this->jwtTokenUser = $data['token'];
		return $this->jwtTokenUser;
	}

	/**
	 * Authentifie l'utilisateur ou l'administration de test avec un token spécifique.
	 *
	 * @param bool $admin Indique si l'utilisateur de test doit être un administrateur.
	 * @return \ApiPlatform\Symfony\Bundle\Test\Client Le client authentifié.
	 */
	protected function createAuthenticatedClient(bool $admin = false): Client
	{
		$token = $admin ? $this->getTokenAdmin() : $this->getTokenUser();

		// Créer le client avec l'option 'auth_bearer'
		return static::createClient([], [
			'auth_bearer' => $token,
		]);
	}

	/**
	 * Crée un utilisateur unique avec un email unique.
	 *
	 * @param string|null $email
	 * @param string|null $password
	 * @param array $roles
	 * @return array Contient le client authentifié, l'IRI et l'ID de l'utilisateur.
	 */
	public function createUniqueUser(string $email = null, string $password = null, array $roles = ['ROLE_USER']): array
	{
		/** @var EntityManagerInterface */
		$entityManager = self::getContainer()->get('doctrine')->getManager();

		// Générer un email unique si non fourni
		if ($email === null) {
			$email = 'user_' . uniqid() . '@example.com';
		}

		// Générer un mot de passe unique si non fourni
		if ($password === null) {
			$password = 'UserPassword+' . uniqid();
		}

		// Créer l'utilisateur
		$user = new Utilisateur();
		$user->setPrenom('Test');
		$user->setNom('User');
		$user->setEmail($email);
		$user->setTelephone('0668747201');
		$user->setRoles($roles);
		$user->setEmailValide(true);

		// Encoder le mot de passe
		/** @var UserPasswordHasherInterface */
		$passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
		$hashedPassword = $passwordHasher->hashPassword($user, $password);
		$user->setPassword($hashedPassword);

		$entityManager->persist($user);
		$entityManager->flush();

		// Authentifier l'utilisateur pour obtenir le token JWT
		$client = static::createClient();
		$response = $client->request('POST', '/api/login_check', [
			'json' => [
				'email' => $email,
				'password' => $password,
			],
		]);

		if ($response->getStatusCode() !== Response::HTTP_OK) {
			throw new \Exception('Échec de l\'authentification de l\'utilisateur unique.');
		}

		$data = $response->toArray();
		if (!isset($data['token'])) {
			throw new \Exception('Token JWT non trouvé pour l\'utilisateur unique.');
		}

		$token = $data['token'];

		// Créer le client authentifié
		$authenticatedClient = static::createClient([], [
			'auth_bearer' => $token,
		]);

		// Récupérer l'IRI de l'utilisateur
		$userIri = '/api/utilisateurs/' . $user->getIdUtilisateur();

		return [
			'client' => $authenticatedClient,
			'iri' => $userIri,
			'id' => $user->getIdUtilisateur(),
			'email' => $email,
		];
	}

	/**
	 * Modifie la méthode getUserId pour accepter un client spécifique.
	 *
	 * @param Client $client
	 * @return int|null
	 */
	public function getUserId(Client $client): ?int
	{
		$client->request('GET', '/api/utilisateurs/me', [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);
		$response = $client->getResponse();
		$this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'La requête pour récupérer l\'ID de l\'utilisateur a échoué.');

		$data = json_decode($response->getContent(), true);
		return isset($data['id_utilisateur']) ? (int) $data['id_utilisateur'] : null;
	}

	/**
	 * Modifie la méthode getUserIri pour accepter un client spécifique et récupérer l'IRI via l'ID.
	 *
	 * @param Client $client
	 * @return string|null
	 */
	public function getUserIri(Client $client): ?string
	{
		// Appelle la méthode getUserId pour récupérer l'ID de l'utilisateur
		$userId = $this->getUserId($client);


		// Vérifie si l'ID a bien été récupéré
		if ($userId === null) {
			throw new \RuntimeException('Impossible de récupérer l\'ID de l\'utilisateur.');
		}

		// Génère l'IRI à partir de l'ID
		$userIri = '/api/utilisateurs/' . $userId;

		return $userIri;
	}

	// Méthode pour créer un transporteur
	public function createTransporteur(): string
	{
		// Créer un administrateur
		$client = $this->createAuthenticatedClient(true);

		// Nom du transporteur unique
		$nomTransporteur = 'Colissimo' . uniqid();

		// Créer un transporteur via l'administrateur
		$client->request('POST', '/api/transporteurs', [
			'json' => [
				'nom' => $nomTransporteur
			]
		]);

		// Vérifier le statut de la réponse
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		// Retourner l'IRI du transporteur
		return $client->getResponse()->toArray()['@id'];
	}

	/**
	 * Méthode pour créer une adresse
	 *
	 * @param \ApiPlatform\Symfony\Bundle\Test\Client $client Le client authentifié
	 * @param string $utilisateurIri L'Iri de l'utilisateur
	 * @return string L'Iri de l'adresse créée
	 */
	public function createAdresse($client, string $utilisateurIri): string
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
				'similaire' => true,
			],
		]);

		// Vérifier que l'adresse a été créée avec succès
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'L\'adresse n\'a pas été créée correctement.');
		$data = $response->toArray();
		return $data['@id'];
	}

	// Méthode pour créer un produit
	public function createProduit()
	{
		// Créer un client authentifié en tant qu'administrateur
		$client = $this->createAuthenticatedClient(true);

		// Créer une catégorie
		$categorieNom = 'Catégorie Test' . uniqid();
		// Effectuer une requête POST pour créer une nouvelle catégorie
		$responseCategorie = $client->request('POST', '/api/categories', [
			'json' => [
				'nom' => $categorieNom
			]
		]);
		// Vérifier que la catégorie a été créée avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseCategorie->getStatusCode());
		// Récupérer les données de la catégorie créée sous forme de tableau
		$responseCategorieArray = $responseCategorie->toArray();
		// Récupérer l'IRI de la catégorie créée
		$categorieIri = $responseCategorieArray['@id'];

		// Créer une TVA
		$responseTva = $client->request('POST', '/api/tvas', [
			'json' => [
				'taux' => '20.00'
			]
		]);
		// Vérifier que la TVA a été créée avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseTva->getStatusCode());
		// Récupérer les données de la TVA créée sous forme de tableau
		$responseTvaArray = $responseTva->toArray();
		// Récupérer l'IRI de la TVA créée
		$tvaIri = $responseTvaArray['@id'];

		// Créer un produit
		$produitNom = 'Produit Test' . uniqid();
		// Description du produit à créer
		$produitDescription = 'Description du produit test' . uniqid();
		// Reference du produit à créer
		$produit = new Produit();
		$produitReference = $produit->generateProductReference();
		// Effectuer une requête POST pour créer un nouveau produit
		$responseProduit = $client->request('POST', '/api/produits', [
			'json' => [
				'reference' => $produitReference,
				'nom' => $produitNom,
				'description' => $produitDescription,
				'prix_ht' => '99.99',
				'tva' => $tvaIri,
				'categories' => [$categorieIri],
				'stock' => 10,
			]
		]);
		echo "\n1.\n";
		// Vérifier que le produit a été créé avec succès
		$this->assertSame(Response::HTTP_CREATED, $responseProduit->getStatusCode());

		// Retourner l'IRI du produit créé
		return $responseProduit->toArray()['@id'];
	}

	/**
	 * Crée une catégorie avec un nom unique.
	 *
	 * @param \ApiPlatform\Symfony\Bundle\Test\Client $client Le client authentifié.
	 * @return string L'Iri de la catégorie créée.
	 */
	public function createCategorie($client): string
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

	public function createCommandeTest(Client $client = null): string
	{
		if ($client == null) {
			$client = $this->createAuthenticatedClient(true);
			// Créer un utilisateur pour la commande
			$utilisateurTest = new UtilisateurTest();
			// Créer un utilisateur basique
			$utilisateurBasique = $utilisateurTest->createUtilisateur($client);
			// Réccupérer l'IRI de l'utilisateur
			$utilisateurIri = $utilisateurBasique['iri'];
		} else {
			$utilisateurIri = $this->getUserIri($client);
		}

		//Création du transporteur
		$transporteur = $this->createTransporteur();

		// Numéro de suivi aléatoire
		$numeroSuivi = uniqid();

		$client->request('POST', '/api/commandes', [
			'json' => [
				"utilisateur" => $utilisateurIri,
				"total" => "19.99",
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

	public function createCommandeWithFullResponse(Client $client = null): array
	{
		if ($client == null) {
			$client = $this->createAuthenticatedClient(true);
			// Créer un utilisateur pour la commande
			$utilisateurTest = new UtilisateurTest();
			// Créer un utilisateur basique
			$utilisateurBasique = $utilisateurTest->createUtilisateur($client);
			// Réccupérer l'IRI de l'utilisateur
			$utilisateurIri = $utilisateurBasique['iri'];
		} else {
			$utilisateurIri = $this->getUserIri($client);
		}

		// Nom du transporteur
		$transporteur = $this->createTransporteur();

		// Numéro de suivi aléatoire
		$numeroSuivi = uniqid();

		$client->request('POST', '/api/commandes', [
			'json' => [
				"utilisateur" => $utilisateurIri,
				"total" => "19.99",
				"transporteur" => $transporteur,
				"poids" => "1.2",
				"frais_livraison" => "4.95",
				"numero_suivi" => $numeroSuivi
			]
		]);

		// Vérifier le statut de la réponse
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		// Retourner toute la réponse au lieu de l'IRI seulement
		return $client->getResponse()->toArray();
	}

	/**
	 * Crée un état de commande temporaire et retourne son IRI.
	 *
	 * @return string
	 */
	public function createEtatCommandeTest(): string
	{
		$client = $this->createAuthenticatedClient(true);
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
	public function createAuthenticatedClientAsUser(string $email, string $password): Client
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
	 * Crée une méthode de livraison associée à un transporteur et retourne son IRI.
	 *
	 * @param string $transporteurIri L'IRI du transporteur associé.
	 * @return string L'IRI de la méthode de livraison créée.
	 */
	public function createMethodeLivraison(string $transporteurIri): string
	{
		// Créer un administrateur authentifié pour créer la méthode de livraison
		$client = $this->createAuthenticatedClient(true);

		// Nom de la méthode de livraison unique
		$nomMethodeLivraison = 'Livraison Standard ' . uniqid();

		// Crée une nouvelle méthode de livraison via l'administrateur
		$response = $client->request('POST', '/api/methodes_livraison', [
			'json' => [
				'nom' => $nomMethodeLivraison,
				'prix' => '5.99',
				'transporteur' => $transporteurIri,
			]
		]);

		// Vérifie que la méthode de livraison a bien été créée
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		// Retourne l'IRI de la méthode de livraison créée
		return $response->toArray()['@id'];
	}
}
