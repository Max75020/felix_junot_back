<?php

namespace App\Entity;

use App\Repository\MethodeLivraisonRepository;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: MethodeLivraisonRepository::class)]
#[UniqueEntity(
	fields: ['nom', 'transporteur'],
	message: 'Cette méthode de livraison existe déjà pour ce transporteur.'
)]
#[ApiResource(
	normalizationContext: ['groups' => ['methodeLivraison:read']],
	denormalizationContext: ['groups' => ['methodeLivraison:write']],
	operations: [
		// Récupération de toutes les méthodes de livraison (accessible à tous)
		new GetCollection(
			normalizationContext: ['groups' => ['methodeLivraison:read']],
			openapiContext: [
				'summary' => 'Récupère la liste de toutes les méthodes de livraison disponibles.',
				'description' => 'Cette opération permet de récupérer toutes les méthodes de livraison disponibles, incluant les transporteurs associés et les détails de livraison.',
				'responses' => [
					'200' => [
						'description' => 'Liste de toutes les méthodes de livraison.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'array',
									'items' => [
										'$ref' => '#/components/schemas/MethodeLivraison',
									],
								],
							],
						],
					],
				],
			]
		),
		// Récupération d'une méthode de livraison spécifique (accessible à tous)
		new Get(
			normalizationContext: ['groups' => ['methodeLivraison:read']],
			openapiContext: [
				'summary' => 'Récupère une méthode de livraison spécifique.',
				'description' => 'Cette opération permet de récupérer les détails d\'une méthode de livraison spécifique en fonction de son identifiant.',
				'responses' => [
					'200' => [
						'description' => 'Détails de la méthode de livraison.',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/MethodeLivraison',
								],
							],
						],
					],
					'404' => [
						'description' => 'Méthode de livraison non trouvée.',
					],
				],
			]
		),
    // Mise à jour partielle d'une méthode de livraison existante (accessible uniquement aux administrateurs)
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['methodeLivraison:write']],
            openapiContext: [
                'summary' => 'Met à jour partiellement une méthode de livraison existante.',
                'description' => 'Cette opération permet aux administrateurs de mettre à jour partiellement les informations d\'une méthode de livraison spécifique sans modifier tous les champs.',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'nom' => [
                                        'type' => 'string',
                                        'description' => 'Le nom de la méthode de livraison.',
                                        'example' => 'Livraison Standard',
                                    ],
                                    'description' => [
                                        'type' => 'string',
                                        'description' => 'Une description mise à jour de la méthode de livraison.',
                                        'example' => 'Livraison en 48-72 heures pour toutes les commandes.',
                                    ],
                                    'prix' => [
                                        'type' => 'string',
                                        'description' => 'Le coût de la méthode de livraison.',
                                        'example' => '7.99',
                                    ],
                                    'delaiEstime' => [
                                        'type' => 'string',
                                        'description' => 'Le délai estimé de livraison mis à jour.',
                                        'example' => '48-72 heures',
                                    ],
                                    'transporteur' => [
                                        'type' => 'string',
                                        'format' => 'iri',
                                        'description' => 'L\'IRI du transporteur associé à cette méthode de livraison.',
                                        'example' => '/api/transporteurs/1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Méthode de livraison mise à jour avec succès.',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/MethodeLivraison',
                                ],
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Requête invalide. Les données fournies ne sont pas conformes.',
                    ],
                    '403' => [
                        'description' => 'Accès refusé. Seuls les administrateurs peuvent mettre à jour une méthode de livraison.',
                    ],
                    '404' => [
                        'description' => 'Méthode de livraison non trouvée.',
                    ],
                ],
            ]
        ),
		// Création d'une nouvelle méthode de livraison (accessible uniquement aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			denormalizationContext: ['groups' => ['methodeLivraison:write']],
			openapiContext: [
				'summary' => 'Crée une nouvelle méthode de livraison.',
				'description' => 'Cette opération permet aux utilisateurs connectés et aux administrateurs de créer une nouvelle méthode de livraison et de l\'associer à un transporteur spécifique.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'nom' => [
										'type' => 'string',
										'description' => 'Le nom de la méthode de livraison.',
										'example' => 'Livraison Express',
									],
									'description' => [
										'type' => 'string',
										'description' => 'Une description détaillée de la méthode de livraison.',
										'example' => 'Livraison en 24 heures pour toutes les commandes passées avant 14h.',
									],
									'prix' => [
										'type' => 'string',
										'description' => 'Le coût de la méthode de livraison.',
										'example' => '5.99',
									],
									'delaiEstime' => [
										'type' => 'string',
										'description' => 'Le délai estimé de livraison.',
										'example' => '24-48 heures',
									],
									'transporteur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'L\'IRI du transporteur associé à cette méthode de livraison.',
										'example' => '/api/transporteurs/1',
									],
								],
								'required' => ['nom', 'prix', 'transporteur', 'delaiEstime'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Méthode de livraison créée avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/MethodeLivraison',
								],
							],
						],
					],
					'400' => [
						'description' => 'Requête invalide. Les données fournies ne sont pas conformes.',
					],
					'403' => [
						'description' => 'Accès refusé. Seuls les utilisateurs connectés et les administrateurs peuvent créer une méthode de livraison.',
					],
				],
			]
		),
	]
)]
class MethodeLivraison
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(name: 'id_methode_livraison', type: 'integer')]
	#[Groups(['methodeLivraison:read'])]
	private ?int $id_methode_livraison = null;

	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank]
	#[Groups(['methodeLivraison:read', 'methodeLivraison:write'])]
	private ?string $nom = null;

	#[ORM\Column(type: 'text', nullable: true)]
	#[Groups(['methodeLivraison:read', 'methodeLivraison:write'])]
	private ?string $description = null;

	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank]
	#[Groups(['methodeLivraison:read', 'methodeLivraison:write'])]
	private string $prix;

	#[ORM\Column(type: 'string', length: 50, nullable: true)]
	#[Groups(['methodeLivraison:read', 'methodeLivraison:write'])]
	private ?string $delaiEstime = null;

	#[ORM\ManyToOne(targetEntity: Transporteurs::class, inversedBy: 'methodesLivraison')]
	#[ORM\JoinColumn(nullable: false, referencedColumnName: 'id_transporteur')]
	#[Groups(['methodeLivraison:read', 'methodeLivraison:write'])]
	private ?Transporteurs $transporteur = null;

	public function getIdMethodeLivraison(): ?int
	{
		return $this->id_methode_livraison;
	}

	public function getNom(): ?string
	{
		return $this->nom;
	}

	public function setNom(string $nom): static
	{
		$this->nom = $nom;

		return $this;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): static
	{
		$this->description = $description;

		return $this;
	}

	public function getPrix(): ?string
	{
		return $this->prix;
	}

	public function setPrix(string $prix): static
	{
		$this->prix = $prix;

		return $this;
	}

	public function getDelaiEstime(): ?string
	{
		return $this->delaiEstime;
	}

	public function setDelaiEstime(string $delaiEstime): static
	{
		$this->delaiEstime = $delaiEstime;

		return $this;
	}

	public function getTransporteur(): ?Transporteurs
	{
		return $this->transporteur;
	}

	public function setTransporteur(?Transporteurs $transporteur): static
	{
		$this->transporteur = $transporteur;

		return $this;
	}
}
