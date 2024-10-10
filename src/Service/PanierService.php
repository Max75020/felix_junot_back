<?php

namespace App\Service;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Entity\PanierProduit;
use App\Enum\EtatPanier;
use Doctrine\ORM\EntityManagerInterface;

class PanierService
{
	private EntityManagerInterface $entityManager;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function creerPanier(Utilisateur $utilisateur): Panier
	{
		$panier = new Panier();
		$panier->setUtilisateur($utilisateur);
		$panier->setEtat(EtatPanier::OUVERT);
		$this->entityManager->persist($panier);
		$this->entityManager->flush();

		return $panier;
	}

	public function ajouterProduitAuPanier(Panier $panier, Produit $produit, int $quantite): void
	{
		// Logique pour ajouter le produit au panier
		$panierProduit = $panier->getPanierProduits()->filter(function ($item) use ($produit) {
			return $item->getProduit() === $produit;
		})->first();

		if ($panierProduit) {
			$panierProduit->setQuantite($panierProduit->getQuantite() + $quantite);
		} else {
			$panierProduit = new PanierProduit();
			$panierProduit->setPanier($panier);
			$panierProduit->setProduit($produit);
			$panierProduit->setQuantite($quantite);
			$panier->addPanierProduit($panierProduit);
		}

		$this->entityManager->persist($panier);
		$this->entityManager->flush();
	}

	public function fermerPanier(Panier $panier): void
	{
		$panier->setEtat(EtatPanier::FERME);
		$this->entityManager->persist($panier);
		$this->entityManager->flush();
	}
}
