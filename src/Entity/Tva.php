<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\TvaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
	normalizationContext: ['groups' => ['tva:read']],
	denormalizationContext: ['groups' => ['tva:write']],
	operations: [
		// Récupération de tous les taux de TVA (accessible uniquement aux administrateurs)
		new GetCollection(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Récupère la liste de tous les taux de TVA disponibles.',
				'description' => 'Cette opération permet aux administrateurs de récupérer tous les taux de TVA enregistrés dans le système.',
				'responses' => [
					'200' => [
						'description' => 'Liste des taux de TVA récupérée avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'array',
									'items' => [
										'type' => 'object',
										'properties' => [
											'id_tva' => ['type' => 'integer', 'description' => 'Identifiant du taux de TVA'],
											'taux' => ['type' => 'string', 'description' => 'Valeur du taux de TVA'],
										],
									],
								],
							],
						],
					],
				],
			]
		),

		// Récupération d'un taux de TVA (accessible à tous)
		new Get(
			openapiContext: [
				'summary' => 'Récupère un taux de TVA spécifique.',
				'description' => 'Permet de récupérer les détails d\'un taux de TVA donné par son identifiant.',
				'responses' => [
					'200' => [
						'description' => 'Détails du taux de TVA récupérés avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_tva' => ['type' => 'integer', 'description' => 'Identifiant du taux de TVA'],
										'taux' => ['type' => 'string', 'description' => 'Valeur du taux de TVA'],
									],
								],
							],
						],
					],
					'404' => [
						'description' => 'Taux de TVA non trouvé.',
					],
				],
			]
		),

		// Modification partielle d'un taux de TVA (accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Met à jour partiellement un taux de TVA existant.',
				'description' => 'Permet aux administrateurs de modifier partiellement un taux de TVA sans avoir à fournir toutes les informations.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'taux' => ['type' => 'string', 'description' => 'Nouveau taux de TVA', 'example' => '20.00'],
								],
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Taux de TVA mis à jour avec succès.',
					],
					'400' => [
						'description' => 'Requête invalide. Les données fournies ne sont pas conformes.',
					],
					'403' => [
						'description' => 'Accès refusé. Seuls les administrateurs peuvent modifier un taux de TVA.',
					],
					'404' => [
						'description' => 'Taux de TVA non trouvé.',
					],
				],
			]
		),

		// Suppression d'un taux de TVA (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime un taux de TVA.',
				'description' => 'Permet aux administrateurs de supprimer un taux de TVA existant.',
				'responses' => [
					'204' => [
						'description' => 'Taux de TVA supprimé avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé. Seuls les administrateurs peuvent supprimer un taux de TVA.',
					],
					'404' => [
						'description' => 'Taux de TVA non trouvé.',
					],
				],
			]
		),

		// Création d'un taux de TVA (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Crée un nouveau taux de TVA.',
				'description' => 'Permet aux administrateurs de créer un nouveau taux de TVA.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'taux' => ['type' => 'string', 'description' => 'Taux de TVA à créer', 'example' => '19.6'],
								],
								'required' => ['taux'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Taux de TVA créé avec succès.',
					],
					'400' => [
						'description' => 'Requête invalide. Les données fournies ne sont pas conformes.',
					],
					'403' => [
						'description' => 'Accès refusé. Seuls les administrateurs peuvent créer un taux de TVA.',
					],
				],
			]
		),
	]
)]
#[ORM\Entity(repositoryClass: TvaRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_taux', columns: ['taux'])]
class Tva
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['tva:read'])]
	private ?int $id_tva = null;

	// Taux de TVA, maintenant de type decimal avec une précision de 5 et une échelle de 2
	#[ORM\Column(type: 'decimal', precision: 5, scale: 2, unique: true)]
	#[Assert\NotBlank(message: "Le taux de TVA est obligatoire.")]
	#[Assert\PositiveOrZero(message: "Le taux de TVA doit être un nombre positif ou zéro.")]
	#[Assert\Range(min: 0, max: 100, notInRangeMessage: "Le taux de TVA doit être compris entre 0 et 100.")]
	#[Groups(['tva:read', 'tva:write'])]
	private ?string $taux = null;

	#[ORM\OneToMany(mappedBy: 'tva', targetEntity: Produit::class)]
	private Collection $produits;

	public function __construct()
	{
		$this->produits = new ArrayCollection();
	}

	// Getters et Setters...

	public function getIdTva(): ?int
	{
		return $this->id_tva;
	}

	public function getTaux(): ?string
	{
		return $this->taux;
	}

	public function setTaux(string $taux): self
	{
		$this->taux = $taux;
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
			$produit->setTva($this);
		}

		return $this;
	}

	public function removeProduit(Produit $produit): self
	{
		if ($this->produits->removeElement($produit)) {
			if ($produit->getTva() === $this) {
				$produit->setTva(null);
			}
		}

		return $this;
	}
}
