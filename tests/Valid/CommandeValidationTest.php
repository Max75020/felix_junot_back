<?php

namespace App\Tests\Valid;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Entity\EtatCommande;
use App\Entity\Transporteur;

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
	private function initializeValidCommande(
		?string $total = '100.00',
		?string $poids = '2.50',
		?string $fraisLivraison = '5.00',
		?Transporteur $transporteur = null
	): Commande {
		// Création d'un utilisateur
		$utilisateur = new Utilisateur();
		$utilisateur->setPrenom('John');
		$utilisateur->setNom('Doe');
		$utilisateur->setEmail('john.doe.' . uniqid() . '@example.com');
		$utilisateur->setPassword('ValidPassw0rd!');
		$utilisateur->setRoles(['ROLE_USER']);
		$utilisateur->setEmailValide(true);

		// Création d'un état de commande
		$etatCommande = new EtatCommande();
		$etatCommande->setLibelle('En attente de paiement');

		// Utiliser un transporteur par défaut si aucun n'est passé en paramètre
		if ($transporteur === null) {
			$transporteur = new Transporteur();
			$transporteur->setNom('Colissimo');
		}

		// Initialisation de la commande
		$commande = new Commande();
		$commande->setUtilisateur($utilisateur);
		$commande->setEtatCommande($etatCommande);
		$commande->setDateCommande(new \DateTime());
		$commande->setTotal($total);
		$commande->setTransporteur($transporteur);
		$commande->setPoids($poids);
		$commande->setFraisLivraison($fraisLivraison);
		$commande->setNumeroSuivi('ABC123');
		$commande->generateReference();

		return $commande;
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

	// Test de validation lorsque le transporteur est absent
	public function testTransporteurObligatoire()
	{
		$commande = $this->initializeValidCommande();
		$commande->setTransporteur(null); // Suppression du transporteur

		$errors = $this->getValidationErrors($commande);
		$this->assertGreaterThan(0, count($errors), 'Aucune erreur de validation trouvée alors que le transporteur est absent.');
		$this->assertEquals("Le transporteur est obligatoire.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le poids est négatif
	public function testPoidsNegatif()
	{
		$commande = $this->initializeValidCommande('100.00', '-5.00'); // Poids négatif
		$errors = $this->getValidationErrors($commande);
		$this->assertGreaterThan(0, count($errors));
		$this->assertEquals("Le poids ne peut pas être négatif.", $errors[0]->getMessage());
	}

	// Test de validation lorsque les frais de livraison sont absents
	public function testFraisLivraisonObligatoire()
	{
		$commande = $this->initializeValidCommande('100.00', '2.50', null); // Frais de livraison absents
		$errors = $this->getValidationErrors($commande);
		$this->assertGreaterThan(0, count($errors), 'Aucune erreur de validation trouvée alors que les frais de livraison sont absents.');
		$this->assertEquals("Les frais de livraison sont obligatoires.", $errors[0]->getMessage());
	}

	// Test de validation lorsque le total est valide
	public function testTotalPositif()
	{
		$commande = $this->initializeValidCommande('150.00'); // Total valide
		$errors = $this->getValidationErrors($commande);
		$this->assertCount(0, $errors, 'Des erreurs de validation ont été trouvées alors que le total est valide.');
	}
}
