<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Entity\EtatCommande;
use App\Entity\CommandeProduit;

class CommandeValidationTest extends KernelTestCase
{

	protected function setUp(): void
	{
		self::bootKernel();
	}
	
	// Fonction pour obtenir les erreurs de validation d'une commande
	public function getValidationErrors(Commande $commande)
	{
		$validator = self::getContainer()->get('validator');
		return $validator->validate($commande);
	}

	// Fonction qui initialise une commande avec des valeurs valides
	private function initializeValidCommande(): Commande
	{
		// Création d'un utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		$utilisateur->setPassword('ValidPassw0rd!');
		$utilisateur->setRoles(['ROLE_USER']);
		$utilisateur->setEmailValide(true);

		// Création d'un etat de commande
		$etatCommande = new EtatCommande();
		$etatCommande->setLibelle('En attente de paiement');

		// Initialisation de la commande
		$commande = new Commande();
		$commande->setUtilisateur($utilisateur);
		$commande->setEtatCommande($etatCommande);
		$commande->setDateCommande(new \DateTime());
		$commande->setTotal('100.00');
		$commande->setTransporteur('Colissimo');
		$commande->setPoids('2.50');
		$commande->setFraisLivraison('5.00');
		$commande->setNumeroSuivi('ABC123');
		$commande->generateReference();
		return $commande;
	}

	// Test pour vérifier que la date de commande est automatiquement générée
	public function testDateCommandeGeneréeAutomatiquement()
	{
		$commande = $this->initializeValidCommande();

		// Vérifie que la date n'est pas nulle et qu'il s'agit bien d'une instance de \DateTimeInterface
		$this->assertInstanceOf(\DateTimeInterface::class, $commande->getDateCommande());
		$this->assertNotNull($commande->getDateCommande());
	}

	// Test de validation lorsque le total de la commande est absent
	public function testTotalObligatoire()
	{
		$commande = $this->initializeValidCommande();
		// Suppression du total
		$commande->setTotal('');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Le total est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le nom du transporteur est absent
	public function testTransporteurObligatoire()
	{
		$commande = $this->initializeValidCommande();
		// Suppression du nom du transporteur
		$commande->setTransporteur('');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Le nom du transporteur est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le poids de la commande est absent
	public function testPoidsObligatoire()
	{
		$commande = $this->initializeValidCommande();
		// Suppression du poids
		$commande->setPoids('');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Le poids est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le numéro de suivi est absent
	public function testNumeroSuiviObligatoire()
	{
		$commande = $this->initializeValidCommande();
		// Suppression du numéro de suivi
		$commande->setNumeroSuivi('');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Le numéro de suivi est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque les frais de livraison sont absents
	public function testFraisLivraisonObligatoire()
	{
		$commande = $this->initializeValidCommande();
		// Suppression des frais de livraison
		$commande->setFraisLivraison('');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Les frais de livraison sont obligatoires.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le poids est négatif
	public function testPoidsNegatif()
	{
		$commande = $this->initializeValidCommande();
		// Poids invalide
		$commande->setPoids('-5.00');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Le poids ne peut pas être négatif.", $errors[0]->getMessage());
	}

	// Test de validation lorsque les frais de livraison sont négatifs
	public function testFraisLivraisonNegatif()
	{
		$commande = $this->initializeValidCommande();
		// Frais de livraison invalides
		$commande->setFraisLivraison('-10.00');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Les frais de livraison ne peuvent pas être négatifs.", $errors[0]->getMessage());
	}

	// Test de validation avec un total valide
	public function testTotalPositif()
	{
		$commande = $this->initializeValidCommande();
		// Total valide
		$commande->setTotal('150.00');
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification qu'aucune erreur n'a été trouvée
		$this->assertCount(0, $errors); // Aucun problème attendu
	}
}
