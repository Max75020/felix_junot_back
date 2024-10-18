<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class GenerateResetTokenController
{
	private EntityManagerInterface $entityManager;
	private MailerInterface $mailer;

	public function __construct(EntityManagerInterface $entityManager, MailerInterface $mailer)
	{
		$this->entityManager = $entityManager;
		$this->mailer = $mailer;
	}

	public function __invoke(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent(), true);
		$email = $data['email'] ?? null;

		if (!$email) {
			return new JsonResponse(['message' => 'Email requis.'], JsonResponse::HTTP_BAD_REQUEST);
		}

		// Rechercher l'utilisateur par email
		$user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

		if (!$user) {
			// Pour des raisons de sécurité, ne pas révéler que l'email n'existe pas
			return new JsonResponse(['message' => 'Si l\'email existe, un lien de réinitialisation a été envoyé.'], JsonResponse::HTTP_OK);
		}

		// Générer un token de réinitialisation unique
		$token = bin2hex(random_bytes(16));
		$user->setTokenReinitialisation($token);
		$this->entityManager->flush();

		// Construire le lien de réinitialisation
		$resetLink = sprintf(
			'%s/new-password?token=%s',
			$_ENV['FRONTEND_URL'],
			$token
		);

		// Envoyer l'email de réinitialisation
		$emailMessage = (new Email())
			->from('no-reply@felix-ceramique.fr')
			->to($user->getEmail())
			->subject('Réinitialisation de votre mot de passe')
			->html(sprintf(
				'<p>Bonjour %s,</p>
                <p>Vous avez demandé une réinitialisation de votre mot de passe. Veuillez cliquer sur le lien suivant pour réinitialiser votre mot de passe :</p>
                <p><a href="%s">Réinitialiser mon mot de passe</a></p>
                <p>Si vous n\'avez pas fait cette demande, veuillez ignorer cet email.</p>',
				htmlspecialchars($user->getPrenom()),
				htmlspecialchars($resetLink)
			));

		$this->mailer->send($emailMessage);

		return new JsonResponse(['message' => 'Si l\'email existe, un lien de réinitialisation a été envoyé.'], JsonResponse::HTTP_OK);
	}
}
