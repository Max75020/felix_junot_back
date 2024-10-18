<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MailController extends AbstractController
{
	private $emailService;

	public function __construct(EmailService $emailService)
	{
		$this->emailService = $emailService;
	}

	/**
	 * @Route("/send-test-email", name="mail_test")
	 */
	public function sendEmail(): Response
	{
		$destinataire = "maxime.duplaissy@gmail.com";
		// Données à envoyer au template
		$data = [
			'nom' => 'Maxime',
			'message' => 'Ceci est un exemple d\'email avec un template!'
		];

		// Appel au service pour envoyer l'email
		$this->emailService->sendEmail(
			$destinataire,
			'Test d\'envoi d\'email avec Symfony et Template',
			'emails/test.html.twig',
			$data
		);

		return new Response('Email de test avec template envoyé avec succès!');
	}
}
