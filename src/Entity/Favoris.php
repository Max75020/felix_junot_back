<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\State\FavorisProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['favoris:read']],
	denormalizationContext: ['groups' => ['favoris:write']],
	operations: [
		// Récupération d'un favori (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user"),

		// Suppression d'un favori (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user"),

		// Création d'un favori (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			processor: FavorisProcessor::class
		)
	]
)]
#[ORM\Entity(repositoryClass: FavorisRepository::class)]
#[UniqueEntity(
	fields: ['utilisateur', 'produit'],
	message: 'Ce produit est déjà dans vos favoris.'
)]
#[ORM\Index(name: 'idx_utilisateur_id', columns: ['utilisateur_id'])]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class Favoris
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['favoris:read'])]
	private ?int $id_favoris = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'favoris')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	#[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
	#[Groups(['favoris:read', 'favoris:write'])]
	private ?Utilisateur $utilisateur = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class)]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	#[Groups(['favoris:read', 'favoris:write'])]
	private ?Produit $produit = null;

	// Getters et Setters...

	public function getIdFavoris(): ?int
	{
		return $this->id_favoris;
	}

	public function getUtilisateur(): ?Utilisateur
	{
		return $this->utilisateur;
	}

	public function setUtilisateur(?Utilisateur $utilisateur): self
	{
		$this->utilisateur = $utilisateur;

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
}
