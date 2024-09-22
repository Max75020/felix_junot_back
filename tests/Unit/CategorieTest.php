<?php

namespace App\Tests\Unit;

use App\Entity\Categorie;
use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

class CategorieTest extends TestCase
{
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
		// Vérification : la catégorie du produit est bien la catégorie
		$this->assertSame($categorie, $produit->getCategorie());
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

		// Suppression du produit de la catégorie
		$categorie->removeProduit($produit);
		// Vérification : le produit a bien été supprimé de la catégorie
		$this->assertCount(0, $categorie->getProduits());
		// Vérification : la catégorie du produit est bien null, la relation a bien été supprimée
		$this->assertNull($produit->getCategorie()); 
	}
}
