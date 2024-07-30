<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PanierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_panier = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'paniers')]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
	private ?Utilisateur $utilisateur = null;

	// Getters et Setters

	public function getIdPanier(): ?int
	{
		return $this->id_panier;
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
}
