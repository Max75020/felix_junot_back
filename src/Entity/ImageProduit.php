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
use App\Repository\ImageProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
	normalizationContext: ['groups' => ['imageProduit:read']],
	denormalizationContext: ['groups' => ['imageProduit:write']],
	operations: [
		// Récupération de toutes les images (accessible à tous)
		new GetCollection(
			openapiContext: [
				'summary' => 'Récupère la liste de toutes les images.',
				'description' => 'Cette opération permet de récupérer toutes les images associées aux produits.',
				'responses' => [
					'200' => [
						'description' => 'Liste des images récupérée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
				],
			]
		),
		// Récupération d'une image (accessible à tous)
		new Get(
			openapiContext: [
				'summary' => 'Récupère une image spécifique.',
				'description' => 'Cette opération permet de récupérer les détails d\'une image spécifique associée à un produit.',
				'responses' => [
					'200' => [
						'description' => 'Détails de l\'image récupérés avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Image non trouvée.',
					],
				],
			]
		),
		// Modification d'une image (accessible uniquement aux administrateurs)
		new Put(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Modifie une image existante.',
				'description' => 'Cette opération permet de modifier les informations d\'une image existante.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit auquel l\'image est associée.',
										'example' => '/api/produits/1'
									],
									'position' => [
										'type' => 'integer',
										'format' => 'int32',
										'description' => 'Position de l\'image dans la liste des images du produit.',
										'example' => 1
									],
									'cover' => [
										'type' => 'boolean',
										'description' => 'Indique si l\'image est la couverture du produit.',
										'example' => true
									],
									'legend' => [
										'type' => 'string',
										'description' => 'Légende de l\'image.',
										'example' => 'Image principale du produit.'
									]
								],
								'required' => ['produit', 'position', 'legend'],
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Image modifiée avec succès.',
					],
					'400' => [
						'description' => 'Erreur de validation ou données incorrectes.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
				],
			]
		),
		// Modification partielle d'une image (accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Modifie partiellement une image de produit existante.',
				'description' => 'Cette opération permet de mettre à jour partiellement les informations d\'une image de produit. Seuls les champs modifiés doivent être envoyés.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit associé à l\'image.',
										'example' => '/api/produits/1'
									],
									'position' => [
										'type' => 'integer',
										'format' => 'int32',
										'description' => 'Position de l\'image dans la liste des images du produit.',
										'example' => 1
									],
									'cover' => [
										'type' => 'boolean',
										'description' => 'Indique si l\'image est la couverture du produit.',
										'example' => true
									],
									'legend' => [
										'type' => 'string',
										'format' => 'string',
										'description' => 'Légende de l\'image.',
										'example' => 'Image principale du produit'
									]
								],
								'required' => []
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'description' => 'Image de produit mise à jour avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_image_produit' => [
											'type' => 'integer',
											'format' => 'int64',
											'description' => 'Identifiant unique de l\'image du produit.',
											'example' => 1
										],
										'produit' => [
											'type' => 'string',
											'format' => 'iri',
											'description' => 'IRI du produit associé à l\'image.',
											'example' => '/api/produits/1'
										],
										'position' => [
											'type' => 'integer',
											'format' => 'int32',
											'description' => 'Position de l\'image dans la liste des images du produit.',
											'example' => 1
										],
										'cover' => [
											'type' => 'boolean',
											'description' => 'Indique si l\'image est la couverture du produit.',
											'example' => true
										],
										'legend' => [
											'type' => 'string',
											'format' => 'string',
											'description' => 'Légende de l\'image.',
											'example' => 'Image principale du produit'
										]
									]
								]
							]
						]
					],
					'400' => [
						'description' => 'Requête invalide. Les données fournies sont incorrectes.',
					],
					'403' => [
						'description' => 'Accès refusé. Vous n\'avez pas les droits pour modifier cette image.',
					],
					'404' => [
						'description' => 'Image de produit non trouvée.',
					]
				]
			]
		),		
		// Suppression d'une image (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime une image existante.',
				'description' => 'Cette opération permet de supprimer une image associée à un produit.',
				'responses' => [
					'204' => [
						'description' => 'Image supprimée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Image non trouvée.',
					],
				],
			]
		),
		// Création d'une nouvelle image (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Crée une nouvelle image pour un produit.',
				'description' => 'Cette opération permet de créer une nouvelle image associée à un produit spécifique.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit auquel l\'image est associée.',
										'example' => '/api/produits/1'
									],
									'position' => [
										'type' => 'integer',
										'format' => 'int32',
										'description' => 'Position de l\'image dans la liste des images du produit.',
										'example' => 1
									],
									'cover' => [
										'type' => 'boolean',
										'description' => 'Indique si l\'image est la couverture du produit.',
										'example' => true
									],
									'legend' => [
										'type' => 'string',
										'description' => 'Légende de l\'image.',
										'example' => 'Image principale du produit.'
									]
								],
								'required' => ['produit', 'position', 'legend'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Nouvelle image créée avec succès.',
					],
					'400' => [
						'description' => 'Erreur de validation ou données incorrectes.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
				],
			]
		),
	]
)]
#[ORM\Entity(repositoryClass: ImageProduitRepository::class)]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class ImageProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['imageProduit:read'])]
	private ?int $id_image_produit = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'images')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	#[Groups(['imageProduit:read', 'imageProduit:write'])]
	private ?Produit $produit = null;

	// Position de l'image dans la liste des images du produit
	#[ORM\Column(type: 'integer', options: ['default' => 0])]
	#[Assert\NotBlank(message: "La position est obligatoire.")]
	#[Assert\PositiveOrZero(message: "La position doit être un nombre positif ou zéro.")]
	#[Groups(['imageProduit:read', 'imageProduit:write', 'produit:read', 'categorie:read'])]
	private ?int $position = 0;

	// Indique si l'image est la couverture du produit
	#[ORM\Column(type: 'boolean', options: ['default' => false])]
	#[Groups(['imageProduit:read', 'imageProduit:write', 'produit:read','categorie:read'])]
	private ?bool $cover = false;

	// Légende de l'image
	#[ORM\Column(type: 'string', length: 128)]
	#[Assert\NotBlank(message: "La légende est obligatoire.")]
	#[Assert\Length(max: 128, maxMessage: "La légende ne peut pas dépasser 128 caractères.")]
	#[Groups(['imageProduit:read', 'imageProduit:write', 'produit:read','categorie:read'])]
	private ?string $legend = null;

    #[ORM\Column(length: 255)]
	#[Groups(['imageProduit:read', 'imageProduit:write', 'produit:read','categorie:read'])]
    private ?string $Chemin = null;

	// Getters et Setters...

	public function getIdImageProduit(): ?int
	{
		return $this->id_image_produit;
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

	public function getPosition(): ?int
	{
		return $this->position;
	}

	public function setPosition(int $position): self
	{
		$this->position = $position;
		return $this;
	}

	public function getCover(): ?bool
	{
		return $this->cover;
	}

	public function setCover(bool $cover): self
	{
		$this->cover = $cover;
		return $this;
	}

	public function getLegend(): ?string
	{
		return $this->legend;
	}

	public function setLegend(string $legend): self
	{
		$this->legend = $legend;
		return $this;
	}

    public function getChemin(): ?string
    {
        return $this->Chemin;
    }

    public function setChemin(string $Chemin): static
    {
        $this->Chemin = $Chemin;

        return $this;
    }
}
