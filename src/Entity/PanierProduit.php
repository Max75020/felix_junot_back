<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\PanierProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\PanierProduitProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['panierProduit:read']],
	denormalizationContext: ['groups' => ['panierProduit:write']],
	operations: [

		// Récupération de tous les paniers-produits (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new GetCollection(security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user"),

		// Récupération d'un panier-produit (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user"),

		// Modification d'un panier-produit (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Put(security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user"),

		// Suppression d'un panier-produit (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user"),

		// Création d'un panier-produit (accessible aux utilisateurs connectés pour leur propre panier et aux administrateurs)
		new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')", processor: PanierProduitProcessor::class)
	]
)]
#[ORM\Entity(repositoryClass: PanierProduitRepository::class)]
#[ORM\Index(name: 'idx_panier_id', columns: ['panier_id'])]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class PanierProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['panierProduit:read'])]
	private ?int $id_panier_produit = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'panierProduits')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	#[Groups(['panierProduit:read', 'panierProduit:write'])]
	private ?Produit $produit = null;

	// Relation ManyToOne avec l'entité Panier
	#[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: 'panierProduits')]
	#[ORM\JoinColumn(name: 'panier_id', referencedColumnName: 'id_panier', nullable: false)]
	#[Assert\NotBlank(message: "Le panier est obligatoire.")]
	#[Groups(['panierProduit:read', 'panierProduit:write'])]
	private ?Panier $panier = null;

	// Quantité du produit dans le panier
	#[ORM\Column(type: 'integer')]
	#[Assert\NotBlank(message: "La quantité est obligatoire.")]
	#[Assert\Positive(message: "La quantité doit être un nombre positif.")]
	#[Groups(['panierProduit:read', 'panierProduit:write'])]
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
