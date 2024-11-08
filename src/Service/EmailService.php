<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
	private $mailer;
	private $twig;

	public function __construct(MailerInterface $mailer, Environment $twig)
	{
		$this->mailer = $mailer;
		$this->twig = $twig;
	}

	/**
	 * Envoie un email avec un template Twig.
	 *
	 * @param string $to L'adresse email du destinataire
	 * @param string $subject Le sujet de l'email
	 * @param string $template Le chemin du template Twig
	 * @param array $data Les données à passer au template
	 * @return void
	 */
	public function sendEmail(string $to, string $subject, string $template, array $data = []): void
	{
		// Générer le contenu HTML à partir du template Twig
		$htmlContent = $this->twig->render($template, $data);

		// Créer l'email
		$email = (new Email())
			->from('felixjunot.ceramique@gmail.com')
			->to($to)
			->subject($subject)
			->html($htmlContent);

		// Envoyer l'email
		$this->mailer->send($email);
	}
}
