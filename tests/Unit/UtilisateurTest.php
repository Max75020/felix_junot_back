<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Entity\Utilisateur;

class UtilisateurTest extends TestCase
{
	// Test pour vérifier la récupération du rôle de l'utilisateur
	public function testGetRoles()
	{
		$utilisateur = new Utilisateur();
		// Définit le rôle de l'utilisateur à 'ROLE_ADMIN'
		$utilisateur->setRole('ROLE_ADMIN');

		// Récupère les rôles de l'utilisateur
		$roles = $utilisateur->getRoles();

		// Vérifie si 'ROLE_ADMIN' est dans les rôles
		$this->assertContains('ROLE_ADMIN', $roles);
		// Vérifie que 'ROLE_USER' est toujours présent
		$this->assertContains('ROLE_USER', $roles);
		// Vérifie qu'il y a exactement 2 rôles (ROLE_USER + ROLE_ADMIN)
		$this->assertCount(2, $roles);
	}

	// Test pour vérifier que le mot de passe peut être défini et récupéré
	public function testSetAndGetPassword()
	{
		$utilisateur = new Utilisateur();
		// Mot de passe défini
		$password = 'Password123!';

		// Définit le mot de passe
		$utilisateur->setPassword($password);
		// Vérifie que le mot de passe est récupéré correctement
		$this->assertEquals($password, $utilisateur->getPassword());
	}

	// Test pour vérifier la gestion des adresses liées à l'utilisateur
	public function testAddAndRemoveAdresse()
	{
		$utilisateur = new Utilisateur();
		$adresse = new \App\Entity\Adresse();

		// Ajoute une adresse à l'utilisateur
		$utilisateur->addAdresse($adresse);
		// Vérifie que l'adresse est bien ajoutée
		$this->assertCount(1, $utilisateur->getAdresses());

		// Supprime l'adresse de l'utilisateur
		$utilisateur->removeAdresse($adresse);
		// Vérifie que l'adresse a bien été supprimée
		$this->assertCount(0, $utilisateur->getAdresses());
	}

	// Test pour vérifier la gestion des favoris
	public function testAddAndRemoveFavoris()
	{
		$utilisateur = new Utilisateur();
		$favori = new \App\Entity\Favoris();

		// Ajoute un favori à l'utilisateur
		$utilisateur->addFavoris($favori);
		// Vérifie que le favori est bien ajouté
		$this->assertCount(1, $utilisateur->getFavoris());

		// Supprime le favori de l'utilisateur
		$utilisateur->removeFavoris($favori);
		// Vérifie que le favori a bien été supprimé
		$this->assertCount(0, $utilisateur->getFavoris());
	}
}
