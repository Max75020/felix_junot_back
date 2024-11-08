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

	/**
	 * @Route("/send-order-confirmation/{orderId}", name="order_confirmation_email")
	 */
	public function sendOrderConfirmationEmail(int $orderId): Response
	{
		// Utilise Doctrine pour récupérer la commande via son id
		$order = $this->entityManager->getRepository(Commande::class)->find($orderId);

		if (!$order) {
			return new Response('Commande non trouvée', Response::HTTP_NOT_FOUND);
		}

		$destinataire = $order->getUser()->getEmail();

		// Données pour le template
		$data = [
			'prenom' => $order->getUser()->getPrenom(),
			'orderNumber' => $order->getOrderNumber(),
			'total' => $order->getTotal(),
			'products' => $order->getProducts(), // Assure-toi que cette méthode existe et renvoie les produits de la commande
			'deliveryAddress' => $order->getDeliveryAddress(),
		];

		$this->emailService->sendEmail(
			$destinataire,
			'Confirmation de votre commande n°' . $order->getOrderNumber(),
			'emails/order_confirmation.html.twig',
			$data
		);

		return new Response('Email de confirmation de commande envoyé avec succès!');
	}
}
