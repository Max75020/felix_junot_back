<?php

namespace App\Service;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Entity\PanierProduit;
use Doctrine\ORM\EntityManagerInterface;

class PanierService
{
	private EntityManagerInterface $entityManager;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	// Crée un nouveau panier pour l'utilisateur
	public function creerPanier(Utilisateur $utilisateur): Panier
	{
		$panier = new Panier();
		$panier->setUtilisateur($utilisateur);
		$panier->setEtat("ouvert");
		$this->entityManager->persist($panier);
		$this->entityManager->flush();

		return $panier;
	}

	// Ajoute un produit dans le panier
	public function ajouterProduitAuPanier(Panier $panier, Produit $produit, int $quantite): void
	{
		$panierProduit = $this->entityManager->getRepository(PanierProduit::class)
			->findOneBy(['panier' => $panier, 'produit' => $produit]);

		if ($panierProduit) {
			// Mise à jour de la quantité si le produit est déjà dans le panier
			$panierProduit->setQuantite($panierProduit->getQuantite() + $quantite);
		} else {
			// Ajouter un nouveau produit au panier
			$panierProduit = new PanierProduit();
			$panierProduit->setPanier($panier);
			$panierProduit->setProduit($produit);
			$panierProduit->setQuantite($quantite);
			$panier->addPanierProduit($panierProduit);
		}

		$this->entityManager->persist($panier);
		$this->entityManager->flush();
	}

	// Incrémente la quantité d'un produit dans le panier
	public function incrementerQuantite(Panier $panier, Produit $produit): void
	{
		$panierProduit = $this->entityManager->getRepository(PanierProduit::class)
			->findOneBy(['panier' => $panier, 'produit' => $produit]);

		if ($panierProduit) {
			$panierProduit->setQuantite($panierProduit->getQuantite() + 1);
			$this->entityManager->flush();
		}
	}

	// Décrémente la quantité d'un produit dans le panier
	public function decrementerQuantite(Panier $panier, Produit $produit): void
	{
		$panierProduit = $this->entityManager->getRepository(PanierProduit::class)
			->findOneBy(['panier' => $panier, 'produit' => $produit]);

		if ($panierProduit) {
			$nouvelleQuantite = $panierProduit->getQuantite() - 1;
			if ($nouvelleQuantite <= 0) {
				// Supprimer le produit si la quantité devient nulle
				$this->entityManager->remove($panierProduit);
			} else {
				$panierProduit->setQuantite($nouvelleQuantite);
			}
			$this->entityManager->flush();
		}
	}
}
