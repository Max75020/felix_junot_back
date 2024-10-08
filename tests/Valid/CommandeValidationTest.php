<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Entity\EtatCommande;
use App\Entity\CommandeProduit;
use App\Entity\Transporteurs;

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
	private function initializeValidCommande(?float $total = 100.00, ?float $fraisLivraison = 5.00): Commande
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

		// Création d'un transporteur
		$transporteur = new Transporteurs();
		$transporteur->setNom('Colissimo');

		// Initialisation de la commande
		$commande = new Commande();
		$commande->setUtilisateur($utilisateur);
		$commande->setEtatCommande($etatCommande);
		$commande->setDateCommande(new \DateTime());

		// Définir le total uniquement s'il est fourni
		if ($total !== null) {
			$commande->setTotal($total);
		}

		$commande->setTransporteur($transporteur);
		$commande->setPoids(2.50);

		// Définir les frais de livraison uniquement s'ils sont fournis
		if ($fraisLivraison !== null) {
			$commande->setFraisLivraison($fraisLivraison);
		}

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
		// Initialisation de la commande sans total
		$commande = $this->initializeValidCommande(null); // On passe null pour ne pas définir le total
	
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
	
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors), 'Aucune erreur de validation trouvée alors que le total est absent.');
	
		// Vérification du message d'erreur spécifique concernant le total
		$this->assertEquals("Le total est obligatoire.", $errors[0]->getMessage());
	}
	

	// Test de validation lorsque le nom du transporteur est absent
	public function testTransporteurObligatoire()
	{
		$commande = $this->initializeValidCommande();
		// Suppression du nom du transporteur
		$commande->setTransporteur(null);
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification que des erreurs ont été trouvées
		$this->assertGreaterThan(0, count($errors));
		// Vérification du message d'erreur
		$this->assertEquals("Le transporteur est obligatoire.", $errors[0]->getMessage());
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
		// Frais de livraison absents
		$commande = $this->initializeValidCommande(100.00,null);
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
		$commande->setPoids(-5.00);
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
		$commande->setFraisLivraison(-10.00);
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
		$commande->setTotal(150.00);
		// Récupération des erreurs de validation
		$errors = $this->getValidationErrors($commande);
		// Vérification qu'aucune erreur n'a été trouvée
		$this->assertCount(0, $errors); // Aucun problème attendu
	}
}
