<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: FavorisRepository::class)]
class Favoris
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_favoris = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'favoris')]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
	private ?Utilisateur $utilisateur = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class)]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	private ?Produit $produit = null;

	// Getters et Setters

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
