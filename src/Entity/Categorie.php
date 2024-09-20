<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use App\Repository\CategorieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ApiResource(
	normalizationContext: ['groups' => ['categorie:read']],
	denormalizationContext: ['groups' => ['categorie:write']],
	operations: [
		// Récupération d'une catégorie (accessible à tous)
		new Get(),

		// Modification complète d'une catégorie (accessible uniquement aux administrateurs)
		new Put(
			security: "is_granted('ROLE_ADMIN')"
		),
		// Modification partielle d'une catégorie (PATCH, accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')"
		),
		// Suppression d'une catégorie (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')"
		),
		// Création d'une nouvelle catégorie (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')"
		)
	]
)]
#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(indexes: [new ORM\Index(name: 'idx_nom', columns: ['nom'])])]
#[UniqueEntity(fields: ['nom'], message: 'Cette catégorie existe déjà.')]
class Categorie
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['categorie:read'])]
	private ?int $id_categorie = null;

	// Nom de la catégorie
	#[ORM\Column(type: 'string', length: 100, unique: true)]
	#[Assert\NotBlank(message: "Le nom de la catégorie est obligatoire.")]
	#[Assert\Length(max: 100, maxMessage: "Le nom de la catégorie ne peut pas dépasser {{ limit }} caractères.")]
	#[Groups(['categorie:read', 'categorie:write'])]
	private ?string $nom = null;

	// Relation OneToMany avec l'entité Produit
	#[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
	#[Groups(['categorie:read'])]
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
