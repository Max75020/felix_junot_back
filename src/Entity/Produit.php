<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;
use App\State\PanierProduitProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['produit:read']],
	denormalizationContext: ['groups' => ['produit:write', 'categorie:write']],
	operations: [

		// Récupération de tous les produits (accessible à tous)
		new GetCollection(),

		// Récupération d'un produit (accessible à tous)
		new Get(),

		// Modification partielle d'un produit (accessible uniquement aux administrateurs)
		new Patch(security: "is_granted('ROLE_ADMIN')"),

		// Suppression d'un produit (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'un produit (accessible uniquement aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN')"),

		// Ajout d'un produit au panier (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
            uriTemplate: '/produits/{id}/add-to-panier',
            processor: PanierProduitProcessor::class,
            openapiContext: [
                'summary' => 'Ajoute un produit au panier',
                'description' => 'Ajoute le produit spécifié au panier de l\'utilisateur connecté.',
            ],
            security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')"
        )
	]
)]
#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[UniqueEntity(fields: ['reference'], message: "Cette référence est déjà utilisée.")]
#[ORM\Index(name: 'idx_reference', columns: ['reference'])]
#[ORM\UniqueConstraint(name: 'uq_reference', columns: ['reference'])]
#[ORM\Index(name: 'idx_nom', columns: ['nom'])]
#[ORM\Index(name: 'idx_prix', columns: ['prix_ttc'])]
#[ORM\Index(name: 'idx_tva', columns: ['tva_id'])]
class Produit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['produit:read'])]
	private ?int $id_produit = null;

	// Référence unique du produit
	#[ORM\Column(type: 'string', length: 15)]
	#[Assert\NotBlank(message: "La référence est obligatoire.")]
	#[Assert\Length(exactly: 15, exactMessage: "La référence doit contenir {{ limit }} caractères.")]
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
	private ?string $prix_ht = null;

	// Relation ManyToMany avec l'entité Categorie
	#[ORM\ManyToMany(targetEntity: Categorie::class, inversedBy: 'produits')]
	#[ORM\JoinTable(
		name: 'produit_categorie',
		joinColumns: [
			new ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit')
		],
		inverseJoinColumns: [
			new ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id_categorie')
		]
	)]
	#[Assert\NotBlank(message: "La catégorie est obligatoire.")]
	#[Groups(['produit:read', 'produit:write', 'categorie:write'])]
	private Collection $categories;

	// Relation ManyToOne avec l'entité Tva
	#[ORM\ManyToOne(targetEntity: Tva::class, inversedBy: 'produits')]
	#[ORM\JoinColumn(name: 'tva_id', referencedColumnName: 'id_tva', nullable: false)]
	#[Assert\NotBlank(message: "La TVA est obligatoire.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?Tva $tva = null;

	// Relation OneToMany avec l'entité Favoris
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: Favoris::class)]
	#[Groups(['produit:read'])]
	private Collection $favoris;

	// Relation OneToMany avec l'entité PanierProduit
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: PanierProduit::class)]
	#[Groups(['produit:read'])]
	private Collection $panierProduits;

	// Relation OneToMany avec l'entité ImageProduit
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: ImageProduit::class, cascade: ['persist', 'remove'])]
	#[Groups(['produit:read', 'produit:write'])]
	private Collection $images;

	// Relation OneToMany avec l'entité CommandeProduit
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: CommandeProduit::class)]
	#[Groups(['produit:read'])]
	private Collection $commandeProduits;

	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le prix est obligatoire.")]
	#[Assert\Positive(message: "Le prix doit être un nombre positif.")]
	#[Groups(['produit:read', 'produit:write'])]
	private ?string $prix_ttc = null;

	#[ORM\Column]
	private ?int $stock = null;

	public function __construct()
	{
		$this->categories = new ArrayCollection();
		$this->favoris = new ArrayCollection();
		$this->panierProduits = new ArrayCollection();
		$this->images = new ArrayCollection();
		$this->commandeProduits = new ArrayCollection();
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

	public function generateProductReference(): string
	{
		// Crée un objet DateTime avec la date et l'heure actuelles
		$now = new \DateTime();

		// Génère une partie aléatoire de 4 chiffres
		$randomPart = mt_rand(1000, 9999);

		// Génère la référence avec le format jour, mois, année + 4 chiffres aléatoires
		$reference = 'REF' . $now->format('dmY') . $randomPart;

		return $reference;
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

	public function getPrixHt(): ?string
	{
		return $this->prix_ht;
	}

	public function setPrixHt(string $prix_ht): self
	{
		$this->prix_ht = $prix_ht;
		// Met à jour le prix TTC dès qu'on modifie le prix HT
		$this->updatePrixTtc();
		return $this;
	}

	public function getCategories(): Collection
	{
		return $this->categories;
	}

	public function setCategories(iterable $categories): self
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
		// Met à jour le prix TTC dès qu'on modifie la TVA
		$this->updatePrixTtc();
		return $this;
	}

	public function getFavoris(): Collection
	{
		return $this->favoris;
	}

	public function addFavori(Favoris $favori): self
	{
		if (!$this->favoris->contains($favori)) {
			$this->favoris[] = $favori;
			$favori->setProduit($this);
		}

		return $this;
	}

	public function removeFavori(Favoris $favori): self
	{
		if ($this->favoris->removeElement($favori)) {
			if ($favori->getProduit() === $this) {
				$favori->setProduit(null);
			}
		}

		return $this;
	}

	public function getPanierProduits(): Collection
	{
		return $this->panierProduits;
	}

	public function addPanierProduit(PanierProduit $panierProduit): self
	{
		if (!$this->panierProduits->contains($panierProduit)) {
			$this->panierProduits[] = $panierProduit;
			$panierProduit->setProduit($this);
		}

		return $this;
	}

	public function removePanierProduit(PanierProduit $panierProduit): self
	{
		if ($this->panierProduits->removeElement($panierProduit)) {
			if ($panierProduit->getProduit() === $this) {
				$panierProduit->setProduit(null);
			}
		}

		return $this;
	}

	public function getImages(): Collection
	{
		return $this->images;
	}

	public function addImage(ImageProduit $image): self
	{
		if (!$this->images->contains($image)) {
			$this->images[] = $image;
			$image->setProduit($this);
		}

		return $this;
	}

	public function removeImage(ImageProduit $image): self
	{
		if ($this->images->removeElement($image)) {
			if ($image->getProduit() === $this) {
				$image->setProduit(null);
			}
		}

		return $this;
	}

	public function getCommandeProduits(): Collection
	{
		return $this->commandeProduits;
	}

	public function addCommandeProduit(CommandeProduit $commandeProduit): self
	{
		if (!$this->commandeProduits->contains($commandeProduit)) {
			$this->commandeProduits[] = $commandeProduit;
			$commandeProduit->setProduit($this);
		}

		return $this;
	}

	public function removeCommandeProduit(CommandeProduit $commandeProduit): self
	{
		if ($this->commandeProduits->removeElement($commandeProduit)) {
			if ($commandeProduit->getProduit() === $this) {
				$commandeProduit->setProduit(null);
			}
		}

		return $this;
	}

	public function getPrixTtc(): ?string
	{
		return $this->prix_ttc;
	}

	public function setPrixTtc(string $prix_ttc): static
	{
		$this->prix_ttc = $prix_ttc;

		return $this;
	}

	/**
	 * Met à jour le prix TTC en fonction du prix HT et du taux de TVA.
	 */
	private function updatePrixTtc(): void
	{
		if (
			$this->prix_ht !== null && $this->tva !== null
		) {
			$this->prix_ttc = $this->prix_ht * (1 + $this->tva->getTaux() / 100);
		}
	}

	public function getStock(): ?int
	{
		return $this->stock;
	}

	public function setStock(int $stock): static
	{
		$this->stock = $stock;

		return $this;
	}
}
