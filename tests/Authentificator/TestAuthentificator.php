<?php

namespace App\Tests\Authentificator;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

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
}
