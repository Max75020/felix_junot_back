<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class MailController extends AbstractController
{
	private $emailService;
	private $entityManager;

	public function __construct(EmailService $emailService, EntityManagerInterface $entityManager)
	{
		$this->emailService = $emailService;
		$this->entityManager = $entityManager;
	}

	/**
	 * @Route("/send-test-email", name="mail_test")
	 */
	public function sendEmail(): Response
	{
		$destinataire = "maxime.duplaissy@gmail.com";
		$data = [
			'nom' => 'Maxime',
			'message' => 'Ceci est un exemple d\'email avec un template!'
		];

		$this->emailService->sendEmail(
			$destinataire,
			'Test d\'envoi d\'email avec Symfony et Template',
			'emails/test.html.twig',
			$data
		);

		return new Response('Email de test avec template envoyé avec succès!');
	}
}
