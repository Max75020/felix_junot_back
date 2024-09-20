<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
	normalizationContext: ['groups' => ['commandeProduit:read']],
	denormalizationContext: ['groups' => ['commandeProduit:write']],
	operations: [
		// Récupération d'une commande-produit (accessible à l'administrateur ou au propriétaire de la commande)
		new Get(security: "is_granted('ROLE_ADMIN') or object.getCommande().getUtilisateur() == user"),

		// Modification complète d'une commande-produit (accessible uniquement aux administrateurs)
		new Put(security: "is_granted('ROLE_ADMIN')"),

		// Suppression d'une commande-produit (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'une nouvelle commande-produit (accessible aux utilisateurs connectés pour leurs propres commandes ou aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN') or object.getCommande().getUtilisateur() == user")
	]
)]
#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
#[ORM\Index(name: 'idx_commande_id', columns: ['commande_id'])]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class CommandeProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['commandeProduit:read'])]
	private ?int $id_commande_produit = null;

	// Relation ManyToOne avec l'entité Commande
	#[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'commandeProduits')]
	#[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id_commande', nullable: false)]
	#[Groups(['commandeProduit:read', 'commandeProduit:write'])]
	private ?Commande $commande = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commandeProduits')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Groups(['commandeProduit:read', 'commandeProduit:write'])]
	private ?Produit $produit = null;

	// Quantité du produit dans la commande
	#[ORM\Column(type: 'integer')]
	#[Assert\NotBlank(message: "La quantité est obligatoire.")]
	#[Assert\Positive(message: "La quantité doit être positive.")]
	#[Assert\Range(min: 1,max: 500,notInRangeMessage: "La quantité doit être comprise entre {{ min }} et {{ max }}.")]
	#[Groups(['commandeProduit:read', 'commandeProduit:write'])]
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
