<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\Utilisateur;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurrentUserController extends AbstractController
{
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	public function __invoke(#[CurrentUser] ?Utilisateur $user): Utilisateur
	{
		$this->logger->info('CurrentUserController invoked');

		if (!$user) {
			$this->logger->warning('Utilisateur non trouvé.');
			throw new NotFoundHttpException('Utilisateur non trouvé.');
		}

		$this->logger->info('Utilisateur trouvé: ' . $user->getEmail());

		return $user;
	}
}
