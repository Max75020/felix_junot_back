<?php

namespace App\Tests\Unit;

use App\Entity\Categorie;
use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

class CategorieTest extends TestCase
{
	// Test des getters et setters
	public function testGettersAndSetters()
	{
		$categorie = new Categorie();

		// Test du setter et getter pour le nom
		$categorie->setNom('Électronique');
		$this->assertEquals('Électronique', $categorie->getNom());
	}

	// Test d'ajout de produit à une catégorie
	public function testAddProduit()
	{
		$categorie = new Categorie();
		$produit = new Produit();

		// Ajoute du produit à la catégorie
		$categorie->addProduit($produit);

		// Vérification : le produit a bien été ajouté à la catégorie
		$this->assertCount(1, $categorie->getProduits());

		// Vérification : la catégorie contient bien ce produit
		$this->assertTrue($categorie->getProduits()->contains($produit));

		// Vérification : le produit contient bien cette catégorie
		$this->assertTrue($produit->getCategories()->contains($categorie));
	}

	// Test de suppression de produit d'une catégorie
	public function testRemoveProduit()
	{
		$categorie = new Categorie();
		$produit = new Produit();

		// Ajout du produit à la catégorie
		$categorie->addProduit($produit);

		// Vérification : le produit a bien été ajouté à la catégorie
		$this->assertCount(1, $categorie->getProduits());
		$this->assertTrue($categorie->getProduits()->contains($produit));

		// Suppression du produit de la catégorie
		$categorie->removeProduit($produit);

		// Vérification : le produit a bien été supprimé de la catégorie
		$this->assertCount(0, $categorie->getProduits());

		// Vérification : la relation est supprimée dans les deux sens
		$this->assertFalse($categorie->getProduits()->contains($produit));
		$this->assertFalse($produit->getCategories()->contains($categorie));
	}
}
