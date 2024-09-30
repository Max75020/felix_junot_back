<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Favoris;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Entity\Categorie;
use App\Entity\Tva;
use Doctrine\ORM\EntityManagerInterface;

class FavorisValidationTest extends KernelTestCase
{
	private EntityManagerInterface $entityManager;

	protected function setUp(): void
	{
		self::bootKernel();
		$this->entityManager = self::getContainer()->get('doctrine')->getManager();
	}

	// Fonction pour obtenir les erreurs de validation d'un favori
	public function getValidationErrors(Favoris $favoris)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($favoris);
	}

	// Fonction qui initialise un Favoris avec les données déjà présentes en base
	private function initializeValidFavoris(): Favoris
	{
		// Création ou récupération de la catégorie
		$categorie = $this->entityManager->getRepository(Categorie::class)->findOneBy(['nom' => 'Catégorie Test']);
		if (!$categorie) {
			$categorie = new Categorie();
			$categorie->setNom('Catégorie Test');
			$this->entityManager->persist($categorie);
			$this->entityManager->flush();
		}

		// Création ou récupération de la TVA
		$tva = $this->entityManager->getRepository(Tva::class)->findOneBy(['taux' => 20.0]);
		if (!$tva) {
			$tva = new Tva();
			$tva->setTaux(20.0);
			$this->entityManager->persist($tva);
			$this->entityManager->flush();
		}

		// Création d'un utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		// Générer un email unique
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		// Mot de passe valide
		$utilisateur->setPassword('ValidPassw0rd75!');
		$utilisateur->setRoles(['ROLE_USER']);

		$this->entityManager->persist($utilisateur);
		$this->entityManager->flush();
		
		$produit = new Produit();
		// Initialisation du produit avec des valeurs valides
		$produit->setTva($tva);
		// Référence valide basée sur la date actuelle + 4 chiffres aléatoires
		$produit->setReference($produit->generateProductReference());
		$produit->setNom('Produit Test');
		$produit->setDescription('Description test');
		$produit->setPrix(19.99);
		$produit->addCategorie($categorie);
		
		// Création du favori
		$favoris = new Favoris();
		$favoris->setUtilisateur($utilisateur);
		$favoris->setProduit($produit);
	
		return $favoris;
	}
	

	// Test de validation de l'unicité d'un favori
	public function testUniqueFavori()
	{
		// Crée et persiste un premier favori valide
		$favoris = $this->initializeValidFavoris();
		$this->entityManager->persist($favoris);
		$this->entityManager->flush(); // Enregistre le premier favori en base

		// Crée un second favori avec les mêmes données (même utilisateur et produit)
		$favorisDuplique = clone $favoris;

		// Vérifie les erreurs de validation
		$errorsDuplication = $this->getValidationErrors($favorisDuplique);

		// Le deuxième favori ne doit pas être valide à cause de la contrainte d'unicité
		$this->assertGreaterThan(0, count($errorsDuplication));
		$this->assertEquals("Ce produit est déjà dans vos favoris.", $errorsDuplication[0]->getMessage());
	}
}
