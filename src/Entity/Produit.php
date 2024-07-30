<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_produit = null;

	// Référence unique du produit
	#[ORM\Column(type: 'string', length: 32)]
	#[Assert\NotBlank(message: "La référence est obligatoire.")]
	private ?string $reference = null;

	// Nom du produit
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	private ?string $nom = null;

	// Description du produit
	#[ORM\Column(type: 'text')]
	#[Assert\NotBlank(message: "La description est obligatoire.")]
	private ?string $description = null;

	// Prix du produit
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le prix est obligatoire.")]
	#[Assert\Positive(message: "Le prix doit être un nombre positif.")]
	private ?float $prix = null;

	// Relation ManyToOne avec l'entité Categorie
	#[ORM\ManyToOne(targetEntity: Categorie::class)]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "La catégorie est obligatoire.")]
	private ?Categorie $categorie = null;

	// Relation ManyToOne avec l'entité Tva
	#[ORM\ManyToOne(targetEntity: Tva::class)]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "La TVA est obligatoire.")]
	private ?Tva $tva = null;

	// Getters et Setters

	public function getIdProduit(): ?int
	{
		return $this->id_produit;
	}

	public function getReference(): ?string
	{
		return $this->reference;
	}

	public function setReference(string $reference): self
	{
		$this->reference = $reference;
		return $this;
	}

	public function getNom(): ?string
	{
		return $this->nom;
	}

	public function setNom(string $nom): self
	{
		$this->nom = $nom;
		return $this;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(string $description): self
	{
		$this->description = $description;
		return $this;
	}

	public function getPrix(): ?float
	{
		return $this->prix;
	}

	public function setPrix(float $prix): self
	{
		$this->prix = $prix;
		return $this;
	}

	public function getCategorie(): ?Categorie
	{
		return $this->categorie;
	}

	public function setCategorie(?Categorie $categorie): self
	{
		$this->categorie = $categorie;
		return $this;
	}

	public function getTva(): ?Tva
	{
		return $this->tva;
	}

	public function setTva(?Tva $tva): self
	{
		$this->tva = $tva;
		return $this;
	}
}
