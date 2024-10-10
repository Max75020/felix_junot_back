<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\PanierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\PanierProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Enum\EtatPanier;

#[ApiResource(
	normalizationContext: ['groups' => ['panier:read']],
	denormalizationContext: ['groups' => ['panier:write']],
	operations: [
		// Récupération du panier (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(
			security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user",
			normalizationContext: ['groups' => ['panier:read']],
			openapiContext: [
				'summary' => 'Récupère les détails d\'un panier spécifique.',
				'description' => 'Cette opération permet de récupérer les détails d\'un panier spécifique appartenant à l\'utilisateur connecté ou de l\'administrateur.',
				'responses' => [
					'200' => [
						'description' => 'Détails du panier récupéré avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/Panier',
								],
							],
						],
					],
					'403' => [
						'description' => 'Accès refusé. Vous ne pouvez accéder qu\'à vos propres paniers ou être administrateur.',
					],
					'404' => [
						'description' => 'Panier non trouvé.',
					],
				],
			]
		),
		// Modification partielle du panier (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Patch(
			security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Met à jour partiellement un panier existant',
				'description' => 'Permet de modifier partiellement un panier pour ajouter, retirer ou mettre à jour des produits.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'etat' => [
										'type' => 'string',
										'description' => 'L\'état actuel du panier (ouvert/fermé).',
										'example' => 'ouvert',
									],
									'prix_total_panier' => [
										'type' => 'string',
										'description' => 'Le prix total actuel des produits dans le panier.',
										'example' => '99.99',
									]
								],
								'required' => []
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Panier mis à jour avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/Panier',
								],
							],
						],
					],
					'400' => [
						'description' => 'Requête invalide. Les données fournies ne sont pas conformes.',
					],
					'403' => [
						'description' => 'Accès refusé. Seuls les utilisateurs connectés ou administrateurs peuvent modifier un panier.',
					],
					'404' => [
						'description' => 'Panier non trouvé.',
					],
				],
			]
		),
		// Suppression du panier (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(
			security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Supprime un panier existant.',
				'description' => 'Cette opération permet de supprimer un panier appartenant à l\'utilisateur connecté ou administrateur.',
				'responses' => [
					'204' => [
						'description' => 'Panier supprimé avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé. Vous ne pouvez supprimer que vos propres paniers ou être administrateur.',
					],
					'404' => [
						'description' => 'Panier non trouvé.',
					],
				],
			]
		),
		// Création d'un panier (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			processor: PanierProcessor::class,
			denormalizationContext: ['groups' => ['panier:write']],
			openapiContext: [
				'summary' => 'Ajoute un produit au panier',
				'description' => 'Ajoute un produit au panier de l\'utilisateur connecté.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'utilisateur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'L\'IRI de l\'utilisateur auquel le panier appartient.',
										'example' => '/api/utilisateurs/1',
									],
									'etat' => [
										'type' => 'string',
										'description' => 'L\'état du panier (ouvert ou fermé).',
										'example' => 'ouvert',
									],
									'prix_total_panier' => [
										'type' => 'string',
										'format' => 'decimal',
										'description' => 'Le prix total des produits dans le panier.',
										'example' => '99.99',
									],
								],
								'required' => ['utilisateur', 'etat'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Panier créé avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/Panier',
								],
							],
						],
					],
					'400' => [
						'description' => 'Données invalides fournies.',
					],
					'403' => [
						'description' => 'Accès refusé. Vous devez être connecté pour ajouter un produit au panier.',
					],
				],
			]
		),
	]
)]
#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ORM\Index(name: 'idx_utilisateur_id', columns: ['utilisateur_id'])]
class Panier
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['panier:read'])]
	private ?int $id_panier = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'paniers')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	#[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
	#[Groups(['panier:read', 'panier:write'])]
	private ?Utilisateur $utilisateur = null;

	// Relation OneToMany avec l'entité PanierProduit
	#[ORM\OneToMany(mappedBy: 'panier', targetEntity: PanierProduit::class, cascade: ['persist', 'remove'])]
	private Collection $panierProduits;

	// État du panier (ouvert ou fermé)
	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\Choice(callback: [EtatPanier::class, 'getValues'], message: 'Valeur non valide pour le champ etat.')]
	private EtatPanier $etat = EtatPanier::OUVERT;

	// Prix total des produits dans le panier
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2, name: 'prix_total_panier')]
	#[Assert\NotBlank(message: "Le prix total du panier est obligatoire.")]
	#[Assert\PositiveOrZero(message: "Le prix total du panier doit être un nombre positif ou nul.")]
	#[Groups(['panier:read'])]
	private string $prix_total_panier = '0.00';

	public function __construct()
	{
		$this->panierProduits = new ArrayCollection();
		// Initialisation explicite de l'état du panier à "ouvert" => nouveau panier sera ouvert par défaut
		$this->etat = EtatPanier::OUVERT;
	}

	// Getters et Setters

	public function getIdPanier(): ?int
	{
		return $this->id_panier;
	}

	public function getUtilisateur(): ?Utilisateur
	{
		return $this->utilisateur;
	}

	public function setUtilisateur(?Utilisateur $utilisateur): self
	{
		$this->utilisateur = $utilisateur;
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
			$panierProduit->setPanier($this);
		}

		return $this;
	}

	public function removePanierProduit(PanierProduit $panierProduit): self
	{
		if ($this->panierProduits->removeElement($panierProduit)) {
			if ($panierProduit->getPanier() === $this) {
				$panierProduit->setPanier(null);
			}
		}

		return $this;
	}

	#[Groups(['panier:read'])]
	public function getEtat(): EtatPanier
	{
		return $this->etat;
	}

	public function setEtat(EtatPanier $etat): self
	{
		$this->etat = $etat;
		return $this;
	}

	// Getters et Setters pour le prix total du panier

	public function getPrixTotalPanier(): string
	{
		return $this->prix_total_panier;
	}

	/**
	 * Vérifie la cohérence du total des produits calculé par rapport aux données actuelles du panier.
	 *
	 * @param string $totalProduitCalculé Le total calculé à vérifier.
	 * @return bool True si le total est correct, False sinon.
	 */
	public function verifierTotalProduits(string $totalProduitCalculé): bool
	{
		$totalActuel = '0.00';

		foreach ($this->getPanierProduits() as $panierProduit) {
			$totalActuel = bcadd($totalActuel, $panierProduit->getPrixTotalProduit(), 2);
		}

		return bccomp($totalActuel, $totalProduitCalculé, 2) === 0;
	}
}
