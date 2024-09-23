<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Favoris;
use App\Entity\Produit;
use App\Entity\Utilisateur;
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
		// Récupère le produit avec id_produit = 1
		$produit = $this->entityManager->getRepository(Produit::class)->find(1);

		// Récupère l'utilisateur avec id_utilisateur = 1
		$utilisateur = $this->entityManager->getRepository(Utilisateur::class)->find(1);

		// Crée un favori avec les objets Utilisateur et Produit
		$favoris = new Favoris();
		$favoris->setUtilisateur($utilisateur); // Associe l'objet Utilisateur au favori
		$favoris->setProduit($produit); // Associe l'objet Produit au favori

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
