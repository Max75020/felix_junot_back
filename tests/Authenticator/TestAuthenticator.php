<?php

namespace App\Tests\Authenticator;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
abstract class TestAuthenticator extends ApiTestCase
{
	private ?string $jwtTokenAdmin = null;
	private ?string $jwtTokenUser = null;

	// Emails fixes pour l'administrateur et l'utilisateur standard
	private string $adminEmail = 'admin@example.com';
	private string $adminPassword = 'AdminPassword+123'; // Au moins 12 caractères
	private string $userEmail = 'user@example.com';
	private string $userPassword = 'UserPassword+123'; // Au moins 12 caractères

	/**
	 * Vérifie et crée un administrateur si nécessaire.
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
	 * Obtient le jeton JWT de l'administrateur.
	 *
	 * @return string Le jeton JWT de l'administrateur.
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
	 * Vérifie et crée un utilisateur standard si nécessaire.
	 */
	protected function ensureUserExists(): void
	{
		/** @var EntityManagerInterface */
		$entityManager = self::getContainer()->get('doctrine')->getManager();

		$user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $this->userEmail]);

		if (!$user) {
			// Créer l'utilisateur standard
			$client = static::createClient();

			$response = $client->request('POST', '/api/utilisateurs', [
				'json' => [
					'prenom' => 'Test',
					'nom' => 'User',
					'email' => $this->userEmail,
					'telephone' => '0668747201',
					'roles' => ['ROLE_USER'],
					'password' => $this->userPassword,
					'email_valide' => true,
				],
			]);

			// Vérifie que l'utilisateur standard a été créé avec succès
			$this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Échec de la création de l\'utilisateur standard.');
		}
	}

	/**
	 * Obtient le jeton JWT d'un utilisateur standard.
	 *
	 * @return string Le jeton JWT de l'utilisateur standard.
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
	 * Crée un client authentifié avec un token spécifique.
	 *
	 * @param bool $admin Indique si le client doit être un administrateur.
	 * @return \ApiPlatform\Symfony\Bundle\Test\Client Le client authentifié.
	 */
	protected function createAuthenticatedClient(bool $admin = false): Client
	{
		$token = $admin ? $this->getTokenAdmin() : $this->getTokenUser();

		return static::createClient([], [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		]);
	}

	/**
	 * Obtient l'Iri de l'utilisateur standard.
	 *
	 * @return string L'Iri de l'utilisateur standard.
	 */
	protected function getUserIri(): string
	{
		/** @var EntityManagerInterface */
		$entityManager = self::getContainer()->get('doctrine')->getManager();

		$user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $this->userEmail]);

		if ($user) {
			return '/api/utilisateurs/' . $user->getIdUtilisateur();
		}

		throw new \Exception('Impossible de récupérer l\'IRI de l\'utilisateur standard.');
	}
}
