<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\State\FavorisProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['favoris:read']],
	denormalizationContext: ['groups' => ['favoris:write']],
	operations: [
		// Récupération de tous les favoris (accessible à tous)
		new GetCollection(
			openapiContext: [
				'summary' => 'Récupère la liste de tous les favoris.',
				'description' => 'Permet de récupérer la liste de tous les favoris disponibles.',
				'responses' => [
					'200' => [
						'description' => 'Liste des favoris récupérée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
				],
			]
		),
		// Récupération d'un favori (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(
			security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Récupère les détails d\'un favori spécifique.',
				'description' => 'Permet de récupérer les informations détaillées d\'un favori particulier appartenant à l\'utilisateur connecté ou accessible à l\'administrateur.',
				'responses' => [
					'200' => [
						'description' => 'Détails du favori récupérés avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Favori non trouvé.',
					],
				],
			]
		),
		// Suppression d'un favori (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(
			security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Supprime un favori existant.',
				'description' => 'Permet de supprimer un favori appartenant à l\'utilisateur connecté ou accessible à l\'administrateur.',
				'responses' => [
					'204' => [
						'description' => 'Favori supprimé avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Favori non trouvé.',
					],
				],
			]
		),
		// Création d'un favori (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			processor: FavorisProcessor::class,
			openapiContext: [
				'summary' => 'Ajoute un produit aux favoris de l\'utilisateur.',
				'description' => 'Permet à un utilisateur connecté ou à un administrateur d\'ajouter un produit aux favoris.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'utilisateur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de l\'utilisateur ajoutant le produit aux favoris.',
										'example' => '/api/utilisateurs/1',
									],
									'produit' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du produit à ajouter aux favoris.',
										'example' => '/api/produits/1',
									],
								],
								'required' => ['utilisateur', 'produit'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Produit ajouté aux favoris avec succès.',
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
#[ORM\Entity(repositoryClass: FavorisRepository::class)]
#[UniqueEntity(
	fields: ['utilisateur', 'produit'],
	message: 'Ce produit est déjà dans vos favoris.'
)]
#[ORM\Index(name: 'idx_utilisateur_id', columns: ['utilisateur_id'])]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class Favoris
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['favoris:read','user:read:item',"user:read:item"])]
	private ?int $id_favoris = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'favoris')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	#[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
	#[Groups(['favoris:read', 'favoris:write'])]
	private ?Utilisateur $utilisateur = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'favoris')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	#[Groups(['favoris:read', 'favoris:write', 'user:read:item'])]
	private ?Produit $produit = null;

	// Getters et Setters...

	public function getIdFavoris(): ?int
	{
		return $this->id_favoris;
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

	public function getProduit(): ?Produit
	{
		return $this->produit;
	}

	public function setProduit(?Produit $produit): self
	{
		$this->produit = $produit;

		return $this;
	}
}
