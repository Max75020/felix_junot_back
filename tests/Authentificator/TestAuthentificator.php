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
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

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
	public function ensureAdminExists(): void
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
	public function getTokenAdmin(): string
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
	public function ensureUserExists(): void
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
	public function getTokenUser(): string
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
	public function createAuthenticatedClient(bool $admin = false): Client
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
				'utilisateur' => $utilisateurIri,
				'type' => 'Facturation',
				'prenom' => 'John',
				'nom' => 'Doe',
				'rue' => '123 Main St',
				'code_postal' => '75001',
				'ville' => 'Paris',
				'pays' => 'France',
				'similaire' => true,
			],
		]);

		// Vérifier que l'adresse a été créée avec succès
		$this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), 'L\'adresse n\'a pas été créée correctement.');
		$data = $response->toArray();
		return $data['@id'];
	}

	// Méthode pour créer un produit
	public function createProduit()
	{
		// Créer un client authentifié en tant qu'administrateur
		//echo "\n--- Début de la création du produit ---\n";
		$client = $this->createAuthenticatedClient(true);
		//echo "\n1. Client administrateur créé avec succès\n";

		// Créer une catégorie
		$categorieNom = 'Catégorie Test' . uniqid();
		//echo "\n2. Création de la catégorie : $categorieNom\n";

		// Effectuer une requête POST pour créer une nouvelle catégorie
		$responseCategorie = $client->request('POST', '/api/categories', [
			'json' => [
				'nom' => $categorieNom
			]
		]);

		// Vérifier que la catégorie a été créée avec succès
		if ($responseCategorie->getStatusCode() !== Response::HTTP_CREATED) {
			//echo "\nErreur : la catégorie n'a pas été créée. Statut : " . $responseCategorie->getStatusCode() . "\n";
		} else {
			//echo "\n3. Catégorie créée avec succès\n";
		}
		$this->assertSame(Response::HTTP_CREATED, $responseCategorie->getStatusCode());

		// Récupérer l'IRI de la catégorie créée
		$categorieIri = $responseCategorie->toArray()['@id'];
		//echo "\n4. IRI de la catégorie récupéré : $categorieIri\n";

		// Créer ou récupérer une TVA existante
		$tauxTva = '20.00';

		// 1. Rechercher une TVA existante avec le taux spécifié
		$responseTvaSearch = $client->request('GET', '/api/tvas', [
			'query' => [
				'taux' => $tauxTva
			]
		]);

		// 2. Vérifier si une TVA existe déjà
		$tvaData = $responseTvaSearch->toArray();
		if (!empty($tvaData['hydra:member'])) {
			// 3. Récupérer l'IRI de la TVA existante
			$tvaIri = $tvaData['hydra:member'][0]['@id'];
			//echo "\n5. TVA existante récupérée avec succès. IRI : $tvaIri\n";
		} else {
			// 4. Si la TVA n'existe pas, la créer
			//echo "\n5. Création de la TVA\n";
			$responseTva = $client->request('POST', '/api/tvas', [
				'json' => [
					'taux' => $tauxTva
				]
			]);

			// 5. Vérifier que la TVA a été créée avec succès
			if ($responseTva->getStatusCode() !== Response::HTTP_CREATED) {
				//echo "\nErreur : la TVA n'a pas été créée. Statut : " . $responseTva->getStatusCode() . "\n";
			} else {
				//echo "\n6. TVA créée avec succès\n";
			}

			$this->assertSame(Response::HTTP_CREATED, $responseTva->getStatusCode());

			// 6. Récupérer l'IRI de la TVA créée
			$tvaIri = $responseTva->toArray()['@id'];
			//echo "\n7. IRI de la TVA récupéré : $tvaIri\n";
		}

		// Créer un produit
		$produitNom = 'Produit Test' . uniqid();
		//echo "\n8. Création du produit : $produitNom\n";

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
		//echo "\n9. Requête pour créer le produit envoyée\n";

		// Vérifier que le produit a été créé avec succès
		if ($responseProduit->getStatusCode() !== Response::HTTP_CREATED) {
			//echo "\nErreur : le produit n'a pas été créé. Statut : " . $responseProduit->getStatusCode() . "\n";
		} else {
			//echo "\n10. Produit créé avec succès\n";
		}
		$this->assertSame(Response::HTTP_CREATED, $responseProduit->getStatusCode());

		// Retourner l'IRI du produit créé
		$produitIri = $responseProduit->toArray()['@id'];
		return $produitIri;
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
		$response = $client->request('POST', '/api/methode_livraisons', [
			'json' => [
				'nom' => $nomMethodeLivraison,
				'description' => 'Livraison en 48-72 heures',
				'prix' => '5.99',
				'delaiEstime' => '48-72 heures',
				'transporteur' => $transporteurIri,
			]
		]);

		// Vérifie que la méthode de livraison a bien été créée
		$this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

		// Retourne l'IRI de la méthode de livraison créée
		return $response->toArray()['@id'];
	}


	public function createStripeClient(): StripeClient
	{
		echo "\n--- Création d'un client Stripe ---\n";
		// Clé API Stripe en mode test (assurez-vous que vous utilisez une clé test et non une clé de production)
		$stripeApiKey = $_ENV['STRIPE_SECRET_KEY'];
		//echo "\n1. Clé API Stripe : $stripeApiKey\n";
		// Création d'une instance du client Stripe
		$stripeClient = new StripeClient($stripeApiKey);
		//echo "\n2. Client Stripe créé avec succès : \n";
		//var_dump($stripeClient);
		//echo "\n\n";

		// Retourner le client Stripe
		return $stripeClient;
	}

	public function confirmPaymentIntent(string $paymentIntentId): array
	{
		try {
			// Créez un client Stripe en utilisant la méthode précédemment définie
			$stripe = $this->createStripeClient();

			// Confirmez le PaymentIntent avec l'ID fourni
			$paymentIntent = $stripe->paymentIntents->confirm($paymentIntentId);

			// Retournez les détails du PaymentIntent
			return $paymentIntent->toArray();
		} catch (ApiErrorException $e) {
			// Gérez les erreurs Stripe et retournez un message d'erreur
			return [
				'error' => true,
				'message' => $e->getMessage()
			];
		}
	}

	public function extractIdFromIri(string $iri): int
	{
		return (int) basename($iri);
	}
}
