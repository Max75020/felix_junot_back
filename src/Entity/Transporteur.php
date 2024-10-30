<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\TransporteurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TransporteurRepository::class)]
#[ApiResource(
	normalizationContext: ['groups' => ['transporteur:read']],
	denormalizationContext: ['groups' => ['transporteur:write']],
	operations: [
		// Récupération de tous les transporteur (accessible à tous)
		new GetCollection(
			normalizationContext: ['groups' => ['transporteur:read']],
			openapiContext: [
				'summary' => 'Récupère la liste de tous les transporteur disponibles.',
				'description' => 'Cette opération permet de récupérer tous les transporteur disponibles.',
				'responses' => [
					'200' => [
						'description' => 'Liste des transporteur récupérée avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'array',
									'items' => [
										'type' => 'object',
										'properties' => [
											'id_transporteur' => ['type' => 'integer', 'description' => 'Identifiant du transporteur'],
											'nom' => ['type' => 'string', 'description' => 'Nom du transporteur'],
										],
									],
								],
							],
						],
					],
				],
			]
		),
		// Récupération d'un transporteur (accessible à tous)
		new Get(
			normalizationContext: ['groups' => ['transporteur:read']],
			openapiContext: [
				'summary' => 'Récupère les détails d\'un transporteur spécifique.',
				'description' => 'Permet de récupérer les détails d\'un transporteur donné par son identifiant.',
				'responses' => [
					'200' => [
						'description' => 'Détails du transporteur récupérés avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_transporteur' => ['type' => 'integer', 'description' => 'Identifiant du transporteur'],
										'nom' => ['type' => 'string', 'description' => 'Nom du transporteur'],
									],
								],
							],
						],
					],
					'404' => [
						'description' => 'Transporteur non trouvé.',
					],
				],
			]
		),
		// Création d'un transporteur (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')",
			denormalizationContext: ['groups' => ['transporteur:write']],
			normalizationContext: ['groups' => ['transporteur:read']],
			openapiContext: [
				'summary' => 'Crée un nouveau transporteur.',
				'description' => 'Cette opération permet de créer un nouveau transporteur. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'nom' => ['type' => 'string', 'description' => 'Nom du transporteur', 'example' => 'Colissimo']
								],
								'required' => ['nom']
							]
						]
					]
				],
				'responses' => [
					'201' => [
						'description' => 'Transporteur créé avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_transporteur' => ['type' => 'integer', 'description' => 'Identifiant du transporteur créé'],
										'nom' => ['type' => 'string', 'description' => 'Nom du transporteur créé'],
									],
								],
							],
						],
					],
					'403' => [
						'description' => 'Accès interdit. Vous devez être administrateur pour créer un transporteur.',
					],
				],
			]
		),
		// Modification partielle d'un transporteur (accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			denormalizationContext: ['groups' => ['transporteur:write']],
			openapiContext: [
				'summary' => 'Met à jour partiellement les informations d\'un transporteur.',
				'description' => 'Permet de mettre à jour partiellement les informations d\'un transporteur existant. Accessible uniquement aux administrateurs.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'nom' => ['type' => 'string', 'description' => 'Nom du transporteur', 'example' => 'Chronopost']
								]
							]
						]
					]
				],
				'responses' => [
					'200' => [
						'description' => 'Transporteur mis à jour avec succès.',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_transporteur' => ['type' => 'integer', 'description' => 'Identifiant du transporteur mis à jour'],
										'nom' => ['type' => 'string', 'description' => 'Nom du transporteur mis à jour'],
									],
								],
							],
						],
					],
					'403' => [
						'description' => 'Accès interdit. Vous devez être administrateur pour mettre à jour un transporteur.',
					],
					'404' => [
						'description' => 'Transporteur non trouvé.',
					],
				],
			]
		),
		// Suppression d'un transporteur (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime un transporteur.',
				'description' => 'Permet de supprimer un transporteur donné. Accessible uniquement aux administrateurs.',
				'responses' => [
					'204' => [
						'description' => 'Transporteur supprimé avec succès.',
					],
					'403' => [
						'description' => 'Accès interdit. Vous devez être administrateur pour supprimer un transporteur.',
					],
					'404' => [
						'description' => 'Transporteur non trouvé.',
					],
				],
			]
		)
	]
)]

class Transporteur
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['transporteur:read, commande:read', 'historiqueEtatCommande:read','commande:read', 'commande:write'])]
	private ?int $id_transporteur = null;

	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom du transporteur est obligatoire.")]
	#[Assert\Length(max: 100, maxMessage: "Le nom du transporteur ne peut pas dépasser {{ limit }} caractères.")]
	#[Groups(['transporteur:read', 'transporteur:write','commande:read', 'commande:write'])]
	private ?string $nom = null;

	// Relation OneToMany avec l'entité Commande
	#[ORM\OneToMany(mappedBy: 'transporteur', targetEntity: Commande::class, cascade: ['persist'])]
	private Collection $commandes;

	// Relation OneToMany avec l'entité MethodeLivraison (cascade persist et remove), si un transporteur est supprimé, ses méthodes de livraison associées le sont également
	#[ORM\OneToMany(mappedBy: 'transporteur', targetEntity: MethodeLivraison::class, cascade: ['persist', 'remove'])]
	#[Groups(['transporteur:read'])]
	private Collection $methodeLivraison;

	public function __construct()
	{
		$this->commandes = new ArrayCollection();
		$this->methodeLivraison = new ArrayCollection();
	}

	public function getIdTransporteur(): ?int
	{
		return $this->id_transporteur;
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

	/**
	 * @return Collection|Commande[]
	 */
	public function getCommandes(): Collection
	{
		return $this->commandes;
	}

	public function addCommande(Commande $commande): self
	{
		if (!$this->commandes->contains($commande)) {
			$this->commandes[] = $commande;
			$commande->setTransporteur($this);
		}
		return $this;
	}

	public function removeCommande(Commande $commande): self
	{
		if ($this->commandes->removeElement($commande)) {
			if ($commande->getTransporteur() === $this) {
				$commande->setTransporteur(null);
			}
		}
		return $this;
	}

	/**
	 * @return Collection<int, MethodeLivraison>
	 */
	public function getmethodeLivraison(): Collection
	{
		return $this->methodeLivraison;
	}

	public function addMethodeLivraison(MethodeLivraison $methodeLivraison): static
	{
		if (!$this->methodeLivraison->contains($methodeLivraison)) {
			$this->methodeLivraison->add($methodeLivraison);
			$methodeLivraison->setTransporteur($this);
		}

		return $this;
	}

	public function removeMethodeLivraison(MethodeLivraison $methodeLivraison): static
	{
		if ($this->methodeLivraison->removeElement($methodeLivraison)) {
			// set the owning side to null (unless already changed)
			if ($methodeLivraison->getTransporteur() === $this) {
				$methodeLivraison->setTransporteur(null);
			}
		}

		return $this;
	}
}
