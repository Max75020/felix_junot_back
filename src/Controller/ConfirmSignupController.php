<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ConfirmSignupController
{
	private EntityManagerInterface $entityManager;
	private LoggerInterface $logger;

	public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
	{
		$this->entityManager = $entityManager;
		$this->logger = $logger;
	}

	public function __invoke(Request $request): JsonResponse
	{
		$this->logger->info('Début de la confirmation d’inscription via le contrôleur.');

		// Récupération du token depuis la requête
		$token = $request->query->get('token');

		if (!$token) {
			$this->logger->warning('Aucun token fourni dans la requête.');
			throw new BadRequestHttpException('Le token est requis.');
		}

		$this->logger->info("Token reçu : {$token}");

		// Recherche de l'utilisateur par le token d'inscription
		$user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
			'tokenInscription' => $token,
		]);

		if (!$user) {
			$this->logger->warning("Utilisateur non trouvé pour le token : {$token}");
			throw new BadRequestHttpException('Token invalide ou expiré.');
		}

		$this->logger->info("Utilisateur trouvé pour le token : {$token}", [
			'id' => $user->getIdUtilisateur(),
			'email' => $user->getEmail()
		]);

		// Vérification si l'utilisateur est déjà validé
		if ($user->isEmailValide()) {
			$this->logger->info("Utilisateur déjà validé : {$user->getEmail()}");
			return new JsonResponse(['message' => 'Votre compte a déjà été validé.'], JsonResponse::HTTP_OK);
		}

		// Validation de l'utilisateur
		try {
			$user->setEmailValide(true);
			$user->setTokenInscription(null); // Invalidation du token
			$this->entityManager->flush();
		} catch (\Exception $e) {
			$this->logger->error("Erreur lors de la validation de l'utilisateur : {$user->getEmail()}", [
				'exception' => $e->getMessage()
			]);
			throw new BadRequestHttpException('Une erreur s\'est produite lors de la validation de l\'utilisateur.');
		}

		$this->logger->info("Inscription confirmée avec succès pour l'utilisateur : {$user->getEmail()}");

		return new JsonResponse(['message' => 'Inscription confirmée avec succès.'], JsonResponse::HTTP_OK);
	}
}
