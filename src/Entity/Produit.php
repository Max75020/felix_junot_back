<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ApiResource(
	normalizationContext: ['groups' => ['produit:read']],
	denormalizationContext: ['groups' => ['produit:write']],
	operations: [
		// Récupération d'un produit (accessible à tous)
		new Get(),

		// Modification d'un produit (accessible uniquement aux administrateurs)
		new Put(security: "is_granted('ROLE_ADMIN')"),

		// Suppression d'un produit (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'un produit (accessible uniquement aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN')")
	]
)]
#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[UniqueEntity(fields: ['reference'], message: "Cette référence est déjà utilisée.")]
#[ORM\Index(name: 'idx_reference', columns: ['reference'])]
#[ORM\UniqueConstraint(name: 'uq_reference', columns: ['reference'])]
#[ORM\Index(name: 'idx_nom', columns: ['nom'])]
#[ORM\Index(name: 'idx_prix', columns: ['prix'])]
class Produit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['produit:read'])]
	private ?int $id_produit = null;

	// Référence unique du produit
	#[ORM\Column(type: 'string', length: 32)]
	#[Assert\NotBlank(message: "La référence est obligatoire.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?string $reference = null;

	// Nom du produit
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?string $nom = null;

	// Description du produit
	#[ORM\Column(type: 'text')]
	#[Assert\NotBlank(message: "La description est obligatoire.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?string $description = null;

	// Prix du produit
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le prix est obligatoire.")]
	#[Assert\Positive(message: "Le prix doit être un nombre positif.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?float $prix = null;

	// Relation ManyToMany avec l'entité Categorie
	#[ORM\ManyToMany(targetEntity: Categorie::class, mappedBy: 'produits')]
	#[Assert\NotBlank(message: "La catégorie est obligatoire.")]
	#[Groups(['produit:read', 'produit:write'])]
	private Collection $categories;

	// Relation ManyToOne avec l'entité Tva
	#[ORM\ManyToOne(targetEntity: Tva::class)]
	#[ORM\JoinColumn(name: 'tva_id', referencedColumnName: 'id_tva', nullable: false)]
	#[Assert\NotBlank(message: "La TVA est obligatoire.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?Tva $tva = null;

	public function __construct()
	{
		$this->categories = new ArrayCollection();
	}

	// Getters et Setters...

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

	public function getCategories(): Collection
	{
		return $this->categories;
	}

	public function setCategories(Collection $categories): self
	{
		// On réinitialise les catégories actuelles en retirant celles qui ne sont plus présentes
		foreach ($this->categories as $categorie) {
			if (!$categories->contains($categorie)) {
				$this->removeCategorie($categorie);
			}
		}

		// On ajoute les nouvelles catégories qui ne sont pas encore associées
		foreach ($categories as $categorie) {
			$this->addCategorie($categorie);
		}

		return $this;
	}

	public function addCategorie(Categorie $categorie): self
	{
		if (!$this->categories->contains($categorie)) {
			$this->categories[] = $categorie;
			// Mise à jour de la relation bidirectionnelle en ajoutant ce produit à la catégorie
			$categorie->addProduit($this);
		}

		return $this;
	}

	public function removeCategorie(Categorie $categorie): self
	{
		if ($this->categories->removeElement($categorie)) {
			// Met à jour la relation bidirectionnelle en supprimant ce produit de la catégorie
			$categorie->removeProduit($this);
		}

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
