<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\PanierProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\PanierProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['panierProduit:read']],
	denormalizationContext: ['groups' => ['panierProduit:write']],
	operations: [
		// Récupération de tous les paniers-produits (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new GetCollection(
			security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Récupère la liste de tous les produits dans les paniers.',
				'description' => 'Cette opération permet de récupérer tous les produits associés aux paniers pour un utilisateur ou un administrateur.',
				'responses' => [
					'200' => [
						'description' => 'Liste de tous les produits dans les paniers.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'array',
									'items' => [
										'type' => 'object',
										'properties' => [
											'id_panier_produit' => ['type' => 'integer', 'example' => 1],
											'produit' => ['type' => 'string', 'format' => 'iri', 'example' => '/api/produits/1'],
											'panier' => ['type' => 'string', 'format' => 'iri', 'example' => '/api/paniers/1'],
											'quantite' => ['type' => 'integer', 'example' => 2],
											'prix_total_produit' => ['type' => 'string', 'format' => 'decimal', 'example' => '20.00']
										]
									]
								]
							]
						]
					],
					'403' => ['description' => 'Accès refusé. Seuls les utilisateurs connectés et les administrateurs peuvent accéder aux produits dans les paniers.']
				]
			]
		),
		// Récupération d'un panier-produit (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(
			security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Récupère un produit spécifique dans un panier.',
				'description' => 'Cette opération permet de récupérer les détails d\'un produit spécifique associé à un panier donné.',
				'responses' => [
					'200' => [
						'description' => 'Détails du produit dans le panier.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_panier_produit' => ['type' => 'integer', 'example' => 1],
										'produit' => ['type' => 'string', 'format' => 'iri', 'example' => '/api/produits/1'],
										'panier' => ['type' => 'string', 'format' => 'iri', 'example' => '/api/paniers/1'],
										'quantite' => ['type' => 'integer', 'example' => 2],
										'prix_total_produit' => ['type' => 'string', 'format' => 'decimal', 'example' => '20.00']
									]
								]
							]
						]
					],
					'403' => ['description' => 'Accès refusé. Seuls les utilisateurs connectés et les administrateurs peuvent accéder aux produits dans les paniers.'],
					'404' => ['description' => 'Produit dans le panier non trouvé.']
				]
			]
		),
		// Modification d'un panier-produit (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Patch(
			security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user",
			denormalizationContext: ['groups' => ['panierProduit:write']],
			openapiContext: [
				'summary' => 'Met à jour partiellement un produit dans le panier.',
				'description' => 'Permet de mettre à jour la quantité ou d\'autres propriétés d\'un produit dans le panier.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit associé.',
										'example' => '/api/produits/1'
									],
									'quantite' => [
										'type' => 'integer',
										'description' => 'Quantité du produit dans le panier.',
										'example' => 2
									],
									'prix_total_produit' => [
										'type' => 'string',
										'format' => 'decimal',
										'description' => 'Prix total du produit dans le panier basé sur la quantité.',
										'example' => '19.98'
									]
								],
								'required' => ['quantite']
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'description' => 'Produit mis à jour avec succès dans le panier.',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/PanierProduit',
								],
							],
						],
					],
					'400' => [
						'description' => 'Requête invalide. Les données fournies ne sont pas conformes.',
					],
					'403' => [
						'description' => 'Accès refusé. Seul le propriétaire du panier ou un administrateur peut modifier le produit dans le panier.',
					],
					'404' => [
						'description' => 'Produit dans le panier non trouvé.',
					],
				],
			]
		),
		// Suppression d'un panier-produit (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(
			security: "is_granted('ROLE_ADMIN') or object.getPanier().getUtilisateur() == user",
			processor: PanierProcessor::class,
			openapiContext: [
				'summary' => 'Supprime un produit du panier.',
				'description' => 'Cette opération permet de supprimer un produit du panier pour les utilisateurs ou les administrateurs.',
				'responses' => [
					'204' => ['description' => 'Produit supprimé du panier avec succès.'],
					'403' => ['description' => 'Accès refusé. Seuls les utilisateurs connectés et les administrateurs peuvent supprimer les produits du panier.'],
					'404' => ['description' => 'Produit dans le panier non trouvé.']
				]
			]
		),
	]
)]
#[ORM\Entity(repositoryClass: PanierProduitRepository::class)]
#[ORM\Index(name: 'idx_panier_id', columns: ['panier_id'])]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class PanierProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['panierProduit:read', 'panierProduit:write','panier:read','user:read:item'])]
	private ?int $id_panier_produit = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'panierProduits')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	#[Groups(['panierProduit:read', 'panierProduit:write', 'panier:read','panier:write','user:read:item'])]
	private ?Produit $produit = null;

	// Relation ManyToOne avec l'entité Panier
	#[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: 'panierProduits')]
	#[ORM\JoinColumn(name: 'panier_id', referencedColumnName: 'id_panier', nullable: false)]
	#[Assert\NotBlank(message: "Le panier est obligatoire.")]
	#[Groups(['panierProduit:read', 'panierProduit:write'])]
	private ?Panier $panier = null;

	// Quantité du produit dans le panier
	#[ORM\Column(type: 'integer')]
	#[Assert\NotBlank(message: "La quantité est obligatoire.")]
	#[Assert\Positive(message: "La quantité doit être un nombre positif.")]
	#[Groups(['panierProduit:read', 'panierProduit:write', 'panier:read','panier:write','user:read:item'])]
	private int $quantite = 1;

	// Prix total du produit dans le panier
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2, name: 'prix_total_produit')]
	#[Assert\NotBlank(message: "Le prix total du produit est obligatoire.")]
	#[Assert\GreaterThanOrEqual(value: 0, message: "Le prix total du produit ne peut pas être négatif.")]
	#[Groups(['panierProduit:read', 'panierProduit:write', 'panier:read','panier:write','user:read:item'])]
	private string $prix_total_produit = '0.00';

	// Getters et Setters

	public function getIdPanierProduit(): ?int
	{
		return $this->id_panier_produit;
	}

	public function getProduit(): ?Produit
	{
		return $this->produit;
	}

	public function setProduit(?Produit $produit): self
	{
		$this->produit = $produit;
		$this->recalculatePrixTotalProduit(); // Recalculer automatiquement le prix total du produit

		return $this;
	}

	public function getPanier(): ?Panier
	{
		return $this->panier;
	}

	public function setPanier(?Panier $panier): self
	{
		$this->panier = $panier;
		return $this;
	}

	public function getQuantite(): ?int
	{
		return $this->quantite;
	}

	public function setQuantite(int $quantite): self
	{
		$this->quantite = $quantite;
		$this->recalculatePrixTotalProduit(); // Recalculer automatiquement le prix total du produit

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

	/**
	 * Recalcule le prix total du produit en fonction de la quantité et du prix TTC.
	 */
	public function recalculatePrixTotalProduit(): void
	{
		if ($this->produit && $this->quantite > 0 && $this->produit->getPrixTtc() > 0) {
			$this->prix_total_produit = bcmul((string) $this->quantite, $this->produit->getPrixTtc(), 2);
		} else {
			$this->prix_total_produit = '0.00';
		}
	}
}
