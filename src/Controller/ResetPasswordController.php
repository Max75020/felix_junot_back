<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController
{
	private EntityManagerInterface $entityManager;
	private UserPasswordHasherInterface $passwordHasher;

	public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
	{
		$this->entityManager = $entityManager;
		$this->passwordHasher = $passwordHasher;
	}

	public function __invoke(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		$token = $data['token'] ?? null;
		$newPassword = $data['new_password'] ?? null;

		if (!$token || !$newPassword) {
			return new JsonResponse(['message' => 'token et nouveau mot de passe sont requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		// Rechercher l'utilisateur par email et token
		$user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
			'token_reinitialisation' => $token,
		]);

		if (!$user) {
			return new JsonResponse(['message' => 'Token invalide ou utilisateur non trouvé.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		// Réinitialiser le mot de passe
		$hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
		$user->setPassword($hashedPassword);
		$user->setTokenReinitialisation(null); // Invalider le token
		$this->entityManager->flush();

		return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès.'], JsonResponse::HTTP_OK);
	}
}
