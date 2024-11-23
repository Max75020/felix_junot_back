<?php

namespace App\EventListener;

use App\Entity\Utilisateur;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\Event\LifecycleEventArgs as EventLifecycleEventArgs;

class RefreshTokenCleanupListener
{
	private Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function preRemove(EventLifecycleEventArgs $args): void
	{
		$entity = $args->getObject();

		// Vérifiez si l'entité est un Utilisateur
		if (!$entity instanceof Utilisateur) {
			return;
		}

		// Récupérer l'email de l'utilisateur
		$email = $entity->getEmail();

		// Supprimer les refresh tokens associés à cet utilisateur
		$this->connection->executeStatement('DELETE FROM refresh_tokens WHERE username = :email', ['email' => $email]);
	}
}
