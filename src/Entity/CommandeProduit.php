<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
class CommandeProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_commande_produit = null;

	// Relation ManyToOne avec l'entité Commande
	#[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'commandeProduits')]
	#[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id_commande', nullable: false)]
	private ?Commande $commande = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commandeProduits')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	private ?Produit $produit = null;

	// Quantité du produit dans la commande
	#[ORM\Column(type: 'integer')]
	#[Assert\NotBlank(message: "La quantité est obligatoire.")]
	#[Assert\Positive(message: "La quantité doit être positive.")]
	private ?int $quantite = null;

	// Getters et Setters

	public function getIdCommandeProduit(): ?int
	{
		return $this->id_commande_produit;
	}

	public function getCommande(): ?Commande
	{
		return $this->commande;
	}

	public function setCommande(?Commande $commande): self
	{
		$this->commande = $commande;
		return $this;
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
