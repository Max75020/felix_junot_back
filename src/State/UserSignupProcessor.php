<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSignupProcessor implements ProcessorInterface
{
	private UserPasswordHasherInterface $passwordHasher;
	private MailerInterface $mailer;
	private EntityManagerInterface $entityManager;

	public function __construct(
		UserPasswordHasherInterface $passwordHasher,
		MailerInterface $mailer,
		EntityManagerInterface $entityManager
	) {
		$this->passwordHasher = $passwordHasher;
		$this->mailer = $mailer;
		$this->entityManager = $entityManager;
	}

	public function process($data, Operation $operation, array $uriVariables = [], array $context = []): void
	{
		// Vérifiez que l'entité est bien un utilisateur
		if (!$data instanceof Utilisateur) {
			return;
		}

		// **1. Cryptage du mot de passe**
		if ($data->getPassword()) {
			$hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPassword());
			$data->setPassword($hashedPassword);
		}

		// **2. Génération du token d'inscription**
		$token = bin2hex(random_bytes(16));
		$data->setTokenInscription($token);

		// **3. Sauvegarde de l'utilisateur avec le token**
		$this->entityManager->persist($data);
		$this->entityManager->flush();

		// **4. Envoi de l'e-mail de confirmation**
		$signupLink = sprintf(
			'%s/confirm-signup?token=%s',
			$_ENV['FRONTEND_URL'],
			$token
		);

		$email = (new Email())
			->from('no-reply@felixjunotceramique.fr')
			->to($data->getEmail())
			->subject('Confirmation de votre inscription')
			->html(sprintf(
				'<p>Bonjour %s,</p>
                <p>Merci de vous être inscrit. Veuillez confirmer votre compte en cliquant sur le lien suivant :</p>
                <p><a href="%s">Confirmer mon compte</a></p>
                <p>Si vous n\'avez pas fait cette demande, veuillez ignorer cet e-mail.</p>',
				htmlspecialchars($data->getPrenom()),
				htmlspecialchars($signupLink)
			));

		$this->mailer->send($email);
	}
}
