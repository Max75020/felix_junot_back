<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CategorieRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ApiResource(
	collectDenormalizationErrors: true,
	normalizationContext: ['groups' => ['categorie:read']],
	denormalizationContext: ['groups' => ['categorie:write']],
	operations: [
		// Récupération de toutes les catégories (accessible à tous)
		new GetCollection(
			openapiContext: [
				'summary' => 'Récupère la collection des catégories',
				'description' => 'Retourne la liste de toutes les catégories disponibles.',
				'responses' => [
					'200' => [
						'description' => 'Collection des catégories récupérée avec succès',
					],
				],
			]
		),
		// Récupération d'une catégorie (accessible à tous)
		new Get(
			openapiContext: [
				'summary' => 'Récupère une catégorie spécifique',
				'description' => 'Retourne les détails d\'une catégorie spécifique par son identifiant.',
				'responses' => [
					'200' => [
						'description' => 'Catégorie récupérée avec succès',
					],
					'404' => [
						'description' => 'Catégorie non trouvée',
					],
				],
			]
		),
		// Modification partielle d'une catégorie (PATCH, accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Modifie partiellement une catégorie',
				'description' => 'Permet de modifier partiellement une catégorie existante. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/merge-patch+json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'nom' => [
										'type' => 'string',
										'description' => 'Le nom de la catégorie',
										'example' => 'Électronique',
									],
								],
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Catégorie modifiée avec succès',
					],
					'400' => [
						'description' => 'Erreur de validation ou données incorrectes',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'est pas administrateur',
					],
					'404' => [
						'description' => 'Catégorie non trouvée',
					],
				],
			]
		),
		// Suppression d'une catégorie (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime une catégorie',
				'description' => 'Permet de supprimer une catégorie existante. Accessible uniquement aux administrateurs.',
				'responses' => [
					'204' => [
						'description' => 'Catégorie supprimée avec succès',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'est pas administrateur',
					],
					'404' => [
						'description' => 'Catégorie non trouvée',
					],
				],
			]
		),

		// Création d'une nouvelle catégorie (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Crée une nouvelle catégorie',
				'description' => 'Permet de créer une nouvelle catégorie. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'nom' => [
										'type' => 'string',
										'description' => 'Le nom de la catégorie',
										'example' => 'Accessoires',
									],
								],
								'required' => ['nom'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Catégorie créée avec succès',
					],
					'400' => [
						'description' => 'Erreur de validation ou données incorrectes',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'est pas administrateur',
					],
				],
			]
		),
	]
)]
#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
#[ORM\Index(name: 'idx_nom', columns: ['nom'])]
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

	#[ORM\ManyToMany(targetEntity: Produit::class, mappedBy: 'categories', cascade: ['persist', 'remove'])]
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
			// Mise à jour de la relation bidirectionnelle si cette catégorie n'est pas déjà associée à ce produit
			$produit->addCategorie($this);
		}

		return $this;
	}

	public function removeProduit(Produit $produit): self
	{
		if ($this->produits->removeElement($produit)) {
			// Mise à jour de la relation bidirectionnelle si cette catégorie est bien associée à ce produit
			$produit->removeCategorie($this);
		}

		return $this;
	}
}
