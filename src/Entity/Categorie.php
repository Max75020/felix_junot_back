<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CategorieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_categorie = null;

	// Nom de la catégorie
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire.")]
	#[Assert\Length(max: 100, maxMessage: "Le nom de la catégorie ne peut pas dépasser {{ limit }} caractères.")]
	private ?string $nom = null;

	// Relation OneToMany avec l'entité Produit
	#[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
	private Collection $produits;

	public function __construct()
	{
		$this->produits = new ArrayCollection();
	}

	// Getters et Setters

	public function getIdCategorie(): ?int
	{
		return $this->id_categorie;
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

	public function getProduits(): Collection
	{
		return $this->produits;
	}

	public function addProduit(Produit $produit): self
	{
		if (!$this->produits->contains($produit)) {
			$this->produits[] = $produit;
			$produit->setCategorie($this);
		}

		return $this;
	}

	public function removeProduit(Produit $produit): self
	{
		if ($this->produits->removeElement($produit)) {
			// set the owning side to null (unless already changed)
			if ($produit->getCategorie() === $this) {
				$produit->setCategorie(null);
			}
		}

		return $this;
	}
}
