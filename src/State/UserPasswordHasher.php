<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Utilisateur;

final readonly class UserPasswordHasher implements ProcessorInterface
{
	public function __construct(
		private ProcessorInterface $processor,
		private UserPasswordHasherInterface $passwordHasher
	) {}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Utilisateur
	{
		if (!$data->getPassword()) {
			return $this->processor->process($data, $operation, $uriVariables, $context);
		}

		// Hash the password
		$hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPassword());
		$data->setPassword($hashedPassword);
		$data->eraseCredentials();

		return $this->processor->process($data, $operation, $uriVariables, $context);
	}
}
