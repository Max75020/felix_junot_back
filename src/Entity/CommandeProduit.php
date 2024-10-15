<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\CommandeProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
	normalizationContext: ['groups' => ['commandeProduit:read']],
	denormalizationContext: ['groups' => ['commandeProduit:write']],
	operations: [
		// Récupération de toutes les commandes-produits (accessible à tous)
		new GetCollection(
			openapiContext: [
				'summary' => 'Récupère la collection de commandes-produits.',
				'description' => 'Permet de récupérer une liste de toutes les commandes-produits.',
				'responses' => [
					'200' => [
						'description' => 'Liste des commandes-produits récupérée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
				],
			]
		),
		// Récupération d'une commande-produit (accessible à l'administrateur ou au propriétaire de la commande)
		new Get(
			security: "is_granted('ROLE_ADMIN') or object.getCommande().getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Récupère une commande-produit.',
				'description' => 'Permet de récupérer les détails d\'une commande-produit spécifique pour un utilisateur ou un administrateur.',
				'responses' => [
					'200' => [
						'description' => 'Commande-produit récupérée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Commande-produit non trouvée.',
					],
				],
			]
		),

		// Modification partielle d'une commande-produit (accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Modifie partiellement une commande-produit.',
				'description' => 'Permet de modifier partiellement une commande-produit existante. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/merge-patch+json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'commande' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de la commande associée',
										'example' => '/api/commandes/1',
									],
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit associé',
										'example' => '/api/produits/1',
									],
									'quantite' => [
										'type' => 'integer',
										'description' => 'Quantité du produit dans la commande',
										'example' => 3,
									],
									'prix_total_produit' => [
										'type' => 'string',
										'description' => 'Prix total du produit dans la commande',
										'example' => '59.99',
									],
								],
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Commande-produit modifiée avec succès.',
					],
					'400' => [
						'description' => 'Erreur de validation ou données incorrectes.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Commande-produit non trouvée.',
					],
				],
			]
		),
		// Suppression d'une commande-produit (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime une commande-produit.',
				'description' => 'Permet de supprimer une commande-produit existante. Accessible uniquement aux administrateurs.',
				'responses' => [
					'204' => [
						'description' => 'Commande-produit supprimée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Commande-produit non trouvée.',
					],
				],
			]
		),
		// Création d'une nouvelle commande-produit (accessible aux utilisateurs connectés pour leurs propres commandes ou aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN') or object.getCommande().getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Crée une nouvelle commande-produit.',
				'description' => 'Permet de créer une nouvelle commande-produit associée à une commande et un produit.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'commande' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de la commande associée',
										'example' => '/api/commandes/1',
									],
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit associé',
										'example' => '/api/produits/1',
									],
									'quantite' => [
										'type' => 'integer',
										'description' => 'Quantité du produit dans la commande',
										'example' => 2,
									],
									'prix_total_produit' => [
										'type' => 'string',
										'description' => 'Prix total du produit dans la commande',
										'example' => '29.99',
									],
								],
								'required' => ['commande', 'produit', 'quantite', 'prix_total_produit'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Commande-produit créée avec succès.',
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
#[ORM\Entity(repositoryClass: CommandeProduitRepository::class)]
#[ORM\Index(name: 'idx_commande_id', columns: ['commande_id'])]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class CommandeProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['commandeProduit:read','commande:read', 'commande:write'])]
	private ?int $id_commande_produit = null;

	// Relation ManyToOne avec l'entité Commande
	#[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'commandeProduits')]
	#[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id_commande', nullable: false)]
	#[Groups(['commandeProduit:read', 'commandeProduit:write'])]
	private ?Commande $commande = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commandeProduits')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Groups(['commandeProduit:read', 'commandeProduit:write','commande:read', 'commande:write'])]
	private ?Produit $produit = null;

	// Quantité du produit dans la commande
	#[ORM\Column(type: 'integer')]
	#[Assert\NotBlank(message: "La quantité est obligatoire.")]
	#[Assert\Positive(message: "La quantité doit être positive.")]
	#[Assert\Range(min: 1, max: 500, notInRangeMessage: "La quantité doit être comprise entre {{ min }} et {{ max }}.")]
	#[Groups(['commandeProduit:read', 'commandeProduit:write','commande:read', 'commande:write'])]
	private int $quantite = 1;

	// Prix total du produit dans la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2, name: 'prix_total_produit')]
	#[Assert\NotBlank(message: "Le prix total du produit est obligatoire.")]
	#[Assert\GreaterThanOrEqual(value: 0, message: "Le prix total du produit ne peut pas être négatif.")]
	#[Groups(['commandeProduit:read', 'commandeProduit:write','commande:read', 'commande:write'])]
	private string $prix_total_produit = '0.00';

	// Getters et Setters

	public function getIdCommandeProduit(): ?int
	{
		return $this->id_commande_produit;
	}

	public function getCommande(): ?Commande
	{
		return $this->commande;
	}

	public function setCommande(?Commande $commande): self
	{
		$this->commande = $commande;
		return $this;
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

	public function getQuantite(): ?int
	{
		return $this->quantite;
	}

	public function setQuantite(int $quantite): self
	{
		$this->quantite = $quantite;
		return $this;
	}

	public function getPrixTotalProduit(): string
	{
		return $this->prix_total_produit;
	}

	public function setPrixTotalProduit(string $prix_total_produit): self
	{
		$this->prix_total_produit = $prix_total_produit;

		return $this;
	}
}
