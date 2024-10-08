<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\TransporteursRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TransporteursRepository::class)]
#[ApiResource(
	normalizationContext: ['groups' => ['transporteurs:read']],
	denormalizationContext: ['groups' => ['transporteurs:write']],
	operations: [
		// Récupération de tous les transporteurs (accessible à tous)
		new GetCollection(
			normalizationContext: ['groups' => ['transporteurs:read']],
			openapiContext: [
				'summary' => 'Récupère la liste de tous les transporteurs disponibles.'
			]
		),
		// Récupération d'un transporteur (accessible à tous)
		new Get(
			normalizationContext: ['groups' => ['transporteurs:read']],
			openapiContext: [
				'summary' => 'Récupère les détails d\'un transporteur spécifique.'
			]
		),
		// Création d'un transporteur (accessible uniquement aux administrateurs)
		new Post(
			security: "is_granted('ROLE_ADMIN')",
			denormalizationContext: ['groups' => ['transporteurs:write']],
			normalizationContext: ['groups' => ['transporteurs:read']],
			openapiContext: [
				'summary' => 'Crée un nouveau transporteur.',
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
								'schema' => ['\$ref' => '#/components/schemas/Transporteurs']
							]
						]
					]
				]
			]
		),
		// Modification partielle d'un transporteur (accessible uniquement aux administrateurs)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			denormalizationContext: ['groups' => ['transporteurs:write']],
			openapiContext: [
				'summary' => 'Met à jour partiellement les informations d\'un transporteur.'
			]
		),
		// Suppression d'un transporteur (accessible uniquement aux administrateurs)
		new Delete(
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Supprime un transporteur.'
			]
		)
	]
)]

class Transporteurs
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['transporteurs:read, commande:read', 'historiqueEtatCommande:read'])]
	private ?int $id_transporteur = null;

	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom du transporteur est obligatoire.")]
	#[Assert\Length(max: 100, maxMessage: "Le nom du transporteur ne peut pas dépasser {{ limit }} caractères.")]
	#[Groups(['transporteurs:read', 'transporteurs:write'])]
	private ?string $nom = null;

	// Relation OneToMany avec l'entité Commande
	#[ORM\OneToMany(mappedBy: 'transporteur', targetEntity: Commande::class)]
	private Collection $commandes;

	public function __construct()
	{
		$this->commandes = new ArrayCollection();
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
}
