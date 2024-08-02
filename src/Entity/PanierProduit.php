<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PanierProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: PanierProduitRepository::class)]
class PanierProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_panier_produit = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class)]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	private ?Produit $produit = null;

	// Relation ManyToOne avec l'entité Panier
	#[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: 'panierProduits')]
	#[ORM\JoinColumn(name: 'panier_id', referencedColumnName: 'id_panier', nullable: false)]
	#[Assert\NotBlank(message: "Le panier est obligatoire.")]
	private ?Panier $panier = null;

	// Quantité du produit dans le panier
	#[ORM\Column(type: 'integer')]
	#[Assert\NotBlank(message: "La quantité est obligatoire.")]
	#[Assert\Positive(message: "La quantité doit être un nombre positif.")]
	private ?int $quantite = null;

	// Getters et Setters

	public function getIdPanierProduit(): ?int
	{
		return $this->id_panier_produit;
	}

	public function getProduit(): ?Produit
	{
		return $this->produit;
	}

	public function setProduit(?Produit $produit): self
	{
		$this->produit = $produit;
		return $this;
	}

	public function getPanier(): ?Panier
	{
		return $this->panier;
	}

	public function setPanier(?Panier $panier): self
	{
		$this->panier = $panier;
		return $this;
	}

	public function getQuantite(): ?int
	{
		return $this->quantite;
	}

	public function setQuantite(int $quantite): self
	{
		$this->quantite = $quantite;
		return $this;
	}
}
