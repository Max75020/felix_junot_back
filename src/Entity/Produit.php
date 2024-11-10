<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
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

#[ApiResource(
	normalizationContext: ['groups' => ['produit:read']],
	denormalizationContext: ['groups' => ['produit:write', 'categorie:write']],
	operations: [
		// Récupération de tous les produits (accessible à tous)
		new GetCollection(
			openapiContext: [
				'summary' => 'Liste tous les produits disponibles',
				'description' => 'Récupère la collection complète de produits accessibles par tous les utilisateurs.',
				'responses' => [
					'200' => [
						'description' => 'Collection de produits récupérée avec succès.',
					],
				],
			]
		),
		// Récupération d'un produit (accessible à tous)
		new Get(
			openapiContext: [
				'summary' => 'Récupère un produit spécifique',
				'description' => 'Permet de récupérer les détails d\'un produit donné par son identifiant. Accessible à tous les utilisateurs.',
				'responses' => [
					'200' => [
						'description' => 'Détails du produit récupérés avec succès.',
					],
					'404' => [
						'description' => 'Produit non trouvé.',
					],
				],
			]
		),
		// Modification partielle d'un produit (accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Modification partielle d\'un produit',
				'description' => 'Permet de modifier partiellement les informations d\'un produit. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/merge-patch+json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'nom' => [
										'type' => 'string',
										'example' => 'Produit Modifié',
										'description' => 'Nom mis à jour du produit.'
									],
									'description' => [
										'type' => 'string',
										'example' => 'Description modifiée pour le produit.',
										'description' => 'Description mise à jour du produit.'
									],
									'prix_ht' => [
										'type' => 'string',
										'example' => '79.99',
										'description' => 'Prix hors taxes mis à jour du produit.'
									],
									'tva' => [
										'type' => 'string',
										'format' => 'iri',
										'example' => '/api/tvas/2',
										'description' => 'IRI de la ressource TVA associée mise à jour.'
									],
									'categories' => [
										'type' => 'array',
										'items' => [
											'type' => 'string',
											'format' => 'iri',
											'example' => '/api/categories/2',
											'description' => 'IRI des nouvelles ressources catégories associées.'
										],
										'description' => 'Liste des catégories mises à jour associées au produit.'
									],
									'images' => [
										'type' => 'array',
										'items' => [
											'type' => 'string',
											'format' => 'iri',
											'example' => '/api/images/2',
											'description' => 'IRI de la nouvelle image associée au produit.'
										],
										'description' => 'Liste des images mises à jour associées au produit.'
									],
									'stock' => [
										'type' => 'integer',
										'example' => 150,
										'description' => 'Quantité en stock mise à jour du produit.'
									],
								],
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Produit mis à jour avec succès.',
					],
					'403' => [
						'description' => 'Accès interdit. Vous devez être administrateur pour modifier un produit.',
					],
				],
			]
		),

		// Suppression d'un produit (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime un produit',
				'description' => 'Permet de supprimer un produit donné. Accessible uniquement aux administrateurs.',
				'responses' => [
					'204' => [
						'description' => 'Produit supprimé avec succès.',
					],
					'403' => [
						'description' => 'Accès interdit. Vous devez être administrateur pour supprimer un produit.',
					],
					'404' => [
						'description' => 'Produit non trouvé.',
					],
				],
			]
		),
		// Création d'un produit (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Crée un nouveau produit',
				'description' => 'Permet de créer un nouveau produit. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'reference' => [
										'type' => 'string',
										'format' => 'alphanumeric',
										'description' => 'Référence unique du produit.',
										'example' => 'REF202410100001',
									],
									'nom' => [
										'type' => 'string',
										'format' => 'string',
										'description' => 'Nom du produit.',
										'example' => 'Produit Test',
									],
									'description' => [
										'type' => 'string',
										'format' => 'text',
										'description' => 'Description détaillée du produit.',
										'example' => 'Ce produit est un excellent choix pour vos besoins quotidiens.',
									],
									'prix_ht' => [
										'type' => 'string',
										'format' => 'decimal',
										'description' => 'Prix hors taxes du produit.',
										'example' => '99.99',
									],
									'tva' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de la ressource TVA associée.',
										'example' => '/api/tvas/1',
									],
									'categories' => [
										'type' => 'array',
										'items' => [
											'type' => 'string',
											'format' => 'iri',
											'description' => 'IRI de la ressource catégorie associée.',
											'example' => '/api/categories/1',
										],
										'description' => 'Liste des catégories associées au produit.',
									],
									'images' => [
										'type' => 'array',
										'items' => [
											'type' => 'string',
											'format' => 'iri',
											'description' => 'IRI de l\'image associée au produit.',
											'example' => '/api/images/1',
										],
										'description' => 'Liste des images associées au produit.',
									],
									'stock' => [
										'type' => 'integer',
										'description' => 'Quantité en stock du produit.',
										'example' => 100,
									],
								],
								'required' => ['reference', 'nom', 'description', 'prix_ht', 'tva', 'categories', 'stock'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Produit créé avec succès.',
					],
					'403' => [
						'description' => 'Accès interdit. Vous devez être administrateur pour créer un produit.',
					],
				],
			]
		),
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
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write', 'categorie:read', 'commande:read', 'user:read:item', 'favoris:read'])]
	private ?int $id_produit = null;

	// Référence unique du produit
	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "La référence est obligatoire.")]
	#[Assert\Length(max: 20, maxMessage: "La référence doit contenir au maximum {{ limit }} caractères.")]
	#[Groups(['produit:read', 'produit:write', 'user:read:item','panier:read', 'panier:write', 'categorie:read', 'commande:read', 'favoris:read'])]
	private ?string $reference = null;


	// Nom du produit
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write', 'categorie:read', 'commande:read', 'user:read:item', 'favoris:read'])]
	private ?string $nom = null;

	// Description du produit
	#[ORM\Column(type: 'text')]
	#[Assert\NotBlank(message: "La description est obligatoire.")]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write'])]
	private ?string $description = null;

	// Prix du produit
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le prix est obligatoire.")]
	#[Assert\Positive(message: "Le prix doit être un nombre positif.")]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write'])]
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
	#[Groups(['produit:read', 'produit:write', 'categorie:write', 'panier:read', 'panier:write'])]
	private Collection $categories;

	// Relation ManyToOne avec l'entité Tva
	#[ORM\ManyToOne(targetEntity: Tva::class, inversedBy: 'produits')]
	#[ORM\JoinColumn(name: 'tva_id', referencedColumnName: 'id_tva', nullable: false)]
	#[Assert\NotBlank(message: "La TVA est obligatoire.")]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write'])]
	private ?Tva $tva = null;

	// Relation OneToMany avec l'entité Favoris
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: Favoris::class)]
	private Collection $favoris;

	// Relation OneToMany avec l'entité PanierProduit
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: PanierProduit::class)]
	private Collection $panierProduits;

	// Relation OneToMany avec l'entité ImageProduit
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: ImageProduit::class, cascade: ['persist', 'remove'])]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write', 'categorie:read'])]
	private Collection $images;

	// Relation OneToMany avec l'entité CommandeProduit
	#[ORM\OneToMany(mappedBy: 'produit', targetEntity: CommandeProduit::class)]
	private Collection $commandeProduits;

	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le prix est obligatoire.")]
	#[Assert\Positive(message: "Le prix doit être un nombre positif.")]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write', 'user:read:item','commande:read'])]
	private ?string $prix_ttc = null;

	#[ORM\Column(type: 'integer', nullable: false)]
	#[Assert\NotNull(message: "Le stock est obligatoire.")]
	#[Groups(['produit:read', 'produit:write', 'panier:read', 'panier:write'])]
	private ?int $stock = null;

	public function __construct()
	{
		$this->categories = new ArrayCollection();
		$this->favoris = new ArrayCollection();
		$this->panierProduits = new ArrayCollection();
		$this->images = new ArrayCollection();
		$this->commandeProduits = new ArrayCollection();
		$this->reference = $this->generateProductReference();
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

		// Génère la référence avec le format jour, mois, année, heure, minute et seconde
		$reference = 'REF' . $now->format('dmYHis');

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

	#[Groups(['commande:read','user:read:item'])]
	// Récupérer le chemin de la cover du produit
	public function getUrlCoverProduit(): ?string
	{
		$cover = null;
		foreach ($this->images as $image) {
			if ($image->isCover()) {
				$cover = $image->getChemin();
			}
		}
		return $cover;
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
