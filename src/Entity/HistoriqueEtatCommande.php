<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\HistoriqueEtatCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ApiResource(
	normalizationContext: ['groups' => ['historiqueEtatCommande:read']],
	denormalizationContext: ['groups' => ['historiqueEtatCommande:write']],
	operations: [
		// Récupération de tous les historiques d'état de commande pour une commande spécifique
		new GetCollection(
			uriTemplate: '/commandes/{id_commande}/historique_etat_commandes',
			uriVariables: [
				'id_commande' => new Link(
					fromClass: Commande::class,
					fromProperty: 'historiqueEtats'
				)
			],
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Récupère la liste des historiques d\'état de commande pour une commande spécifique.',
				'description' => 'Cette opération permet de récupérer tous les historiques d\'état d\'une commande donnée.',
				'responses' => [
					'200' => [
						'description' => 'Liste des historiques d\'état de commande récupérée avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Commande non trouvée.',
					],
				],
			]
		),
		// Récupération d'un historique d'état de commande spécifique pour une commande spécifique
		new Get(
			uriTemplate: '/commandes/{id_commande}/historique_etat_commandes/{id}',
			uriVariables: [
				'id_commande' => new Link(
					fromClass: Commande::class,
					fromProperty: 'historiqueEtats'
				),
				'id' => new Link(fromClass: HistoriqueEtatCommande::class)
			],
			security: "is_granted('ROLE_ADMIN') or object.getCommande().getUtilisateur() == user",
			openapiContext: [
				'summary' => 'Récupère un historique d\'état de commande spécifique pour une commande donnée.',
				'description' => 'Cette opération permet de récupérer les détails d\'un historique d\'état de commande spécifique pour une commande donnée.',
				'responses' => [
					'200' => [
						'description' => 'Détails de l\'historique d\'état de commande récupérés avec succès.',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'a pas les autorisations nécessaires.',
					],
					'404' => [
						'description' => 'Historique d\'état de commande ou commande non trouvée.',
					],
				],
			]
		),
		// Création d'un nouvel historique d'état de commande pour une commande spécifique
		new Post(
			uriTemplate: '/commandes/{id_commande}/historique_etat_commandes',
			uriVariables: [
				'id_commande' => new Link(
					fromClass: Commande::class,
					fromProperty: 'historiqueEtats'
				)
			],
			security: "is_granted('ROLE_ADMIN')",
			openapiContext: [
				'summary' => 'Crée un nouvel historique d\'état de commande pour une commande spécifique.',
				'description' => 'Cette opération permet d\'ajouter un nouvel état à l\'historique d\'une commande donnée.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'etat_commande' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de l\'état de la commande.',
										'example' => '/api/etats_commande/1'
									],
									'date_etat' => [
										'type' => 'string',
										'format' => 'date-time',
										'description' => 'La date de changement d\'état.',
										'example' => '2024-04-27T12:00:00+00:00'
									]
								],
								'required' => ['etat_commande'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Nouvel historique d\'état de commande créé avec succès.',
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
#[ORM\Entity(repositoryClass: HistoriqueEtatCommandeRepository::class)]
#[ORM\Index(name: 'idx_commande_id', columns: ['commande_id'])]
#[ORM\Index(name: 'idx_etat_commande_id', columns: ['etat_commande_id'])]
#[ORM\Index(name: 'idx_date_etat', columns: ['date_etat'])]
class HistoriqueEtatCommande
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['historiqueEtatCommande:read','commande:read', 'commande:write'])]
	private ?int $id_historique_etat_commande = null;

	// Relation ManyToOne avec l'entité Commande
	#[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'historiqueEtats', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id_commande', nullable: false)]
	#[Assert\NotBlank(message: "La commande est obligatoire.")]
	#[Groups(['historiqueEtatCommande:read', 'historiqueEtatCommande:write'])]
	private ?Commande $commande = null;

	// Date de changement d'état de la commande
	#[ORM\Column(type: 'datetime')]
	#[DateTimeNormalizer(format: 'd-m-Y H:i:s')]
	#[Assert\NotBlank(message: "La date de changement d'état est obligatoire.")]
	#[Assert\Type(\DateTimeInterface::class, message: "La date de changement d'état doit être une date valide.")]
	#[Groups(['historiqueEtatCommande:read', 'historiqueEtatCommande:write','commande:read', 'commande:write'])]
	private ?\DateTimeInterface $date_etat = null;

	// Relation ManyToOne avec l'entité EtatCommande
	#[ORM\ManyToOne(targetEntity: EtatCommande::class, inversedBy: 'historiqueEtats')]
	#[ORM\JoinColumn(name: 'etat_commande_id', referencedColumnName: 'id_etat_commande', nullable: false)]
	#[Assert\NotBlank(message: "L'état de la commande est obligatoire.")]
	#[Groups(['historiqueEtatCommande:read', 'historiqueEtatCommande:write','commande:read', 'commande:write'])]
	private ?EtatCommande $etat_commande = null;

	// Constructeur pour initialiser automatiquement la date
	public function __construct()
	{
		// Initialise automatiquement la date à la création de l'objet
		$this->date_etat = new \DateTime();
	}

	// Getters et Setters...

	public function getIdHistoriqueEtatCommande(): ?int
	{
		return $this->id_historique_etat_commande;
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

	public function getDateEtat(): ?\DateTimeInterface
	{
		return $this->date_etat;
	}

	public function setDateEtat(\DateTimeInterface $date_etat): self
	{
		$this->date_etat = $date_etat;
		return $this;
	}

	public function getEtatCommande(): ?EtatCommande
	{
		return $this->etat_commande;
	}

	public function setEtatCommande(?EtatCommande $etat_commande): self
	{
		$this->etat_commande = $etat_commande;
		return $this;
	}
}
