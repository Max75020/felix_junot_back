<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\State\AdresseProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['adresse:read']],
	denormalizationContext: ['groups' => ['adresse:write']],
	operations: [

		// Récupération de toutes les adresses (accessible à tous)
		new GetCollection(
			openapiContext: [
				'summary' => 'Récupère la liste des adresses',
				'description' => 'Retourne une collection d\'adresses disponibles.',
				'responses' => [
					'200' => [
						'description' => 'Liste des adresses récupérées avec succès',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'array',
									'items' => [
										'type' => 'object',
										'properties' => [
											'id_adresse' => [
												'type' => 'integer',
												'description' => 'Identifiant unique de l\'adresse',
												'example' => 1,
											],
											'utilisateur' => [
												'type' => 'string',
												'format' => 'iri',
												'description' => 'L\'IRI de l\'utilisateur associé à cette adresse',
												'example' => '/api/utilisateurs/1',
											],
											'type' => [
												'type' => 'string',
												'description' => 'Le type de l\'adresse (Facturation ou Livraison)',
												'example' => 'Facturation',
											],
											'prenom' => [
												'type' => 'string',
												'description' => 'Le prénom associé à l\'adresse',
												'example' => 'Jean',
											],
											'nom' => [
												'type' => 'string',
												'description' => 'Le nom associé à l\'adresse',
												'example' => 'Dupont',
											],
											'rue' => [
												'type' => 'string',
												'description' => 'La rue ou l\'adresse complète',
												'example' => '123 Rue Principale',
											],
											'code_postal' => [
												'type' => 'string',
												'description' => 'Le code postal de l\'adresse',
												'example' => '75001',
											],
											'ville' => [
												'type' => 'string',
												'description' => 'La ville associée à l\'adresse',
												'example' => 'Paris',
											],
											'pays' => [
												'type' => 'string',
												'description' => 'Le pays de l\'adresse',
												'example' => 'France',
											],
											'telephone' => [
												'type' => 'string',
												'description' => 'Le numéro de téléphone associé',
												'example' => '+33123456789',
											],
											'similaire' => [
												'type' => 'boolean',
												'description' => 'Indique si cette adresse est similaire à une autre adresse déjà existante',
												'example' => false,
											],
										],
										'required' => ['id_adresse', 'utilisateur', 'type', 'prenom', 'nom', 'rue', 'code_postal', 'ville', 'pays'],
									],
								],
							],
						],
					],
				],
			]
		),

		// Récupération d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)",
			openapiContext: [
				'summary' => 'Récupère une adresse spécifique',
				'description' => 'Permet de récupérer les détails d\'une adresse particulière. Accessible uniquement à l\'administrateur ou à l\'utilisateur propriétaire.',
				'responses' => [
					'200' => [
						'description' => 'Adresse récupérée avec succès',
						'content' => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
									'properties' => [
										'id_adresse' => [
											'type' => 'integer',
											'description' => 'Identifiant unique de l\'adresse',
											'example' => 1,
										],
										'utilisateur' => [
											'type' => 'string',
											'format' => 'iri',
											'description' => 'L\'IRI de l\'utilisateur associé à cette adresse',
											'example' => '/api/utilisateurs/1',
										],
										'type' => [
											'type' => 'string',
											'description' => 'Le type d\'adresse (Facturation ou Livraison)',
											'example' => 'Facturation',
										],
										'prenom' => [
											'type' => 'string',
											'description' => 'Le prénom associé à l\'adresse',
											'example' => 'Jean',
										],
										'nom' => [
											'type' => 'string',
											'description' => 'Le nom associé à l\'adresse',
											'example' => 'Dupont',
										],
										'rue' => [
											'type' => 'string',
											'description' => 'La rue ou l\'adresse complète',
											'example' => '123 Rue Principale',
										],
										'code_postal' => [
											'type' => 'string',
											'description' => 'Le code postal de l\'adresse',
											'example' => '75001',
										],
										'ville' => [
											'type' => 'string',
											'description' => 'La ville associée à l\'adresse',
											'example' => 'Paris',
										],
										'pays' => [
											'type' => 'string',
											'description' => 'Le pays de l\'adresse',
											'example' => 'France',
										],
										'telephone' => [
											'type' => 'string',
											'description' => 'Le numéro de téléphone associé',
											'example' => '+33123456789',
										],
										'similaire' => [
											'type' => 'boolean',
											'description' => 'Indique si cette adresse est similaire à une autre adresse déjà existante',
											'example' => false,
										],
									],
									'required' => ['id_adresse', 'utilisateur', 'type', 'prenom', 'nom', 'rue', 'code_postal', 'ville', 'pays'],
								],
							],
						],
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'est ni propriétaire de l\'adresse ni administrateur',
					],
					'404' => [
						'description' => 'Adresse non trouvée',
					],
				],
			]
		),

		// Modification partielle d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Patch(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)",
			openapiContext: [
				'summary' => 'Modifie partiellement une adresse existante',
				'description' => 'Permet de modifier partiellement une adresse. Accessible uniquement à l\'administrateur ou à l\'utilisateur propriétaire.',
				'requestBody' => [
					'content' => [
						'application/merge-patch+json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'utilisateur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'L\'IRI de l\'utilisateur associé',
										'example' => '/api/utilisateurs/1',
									],
									'type' => [
										'type' => 'string',
										'description' => 'Le type d\'adresse (Facturation ou Livraison)',
										'example' => 'Livraison',
									],
									'prenom' => [
										'type' => 'string',
										'description' => 'Le prénom associé à l\'adresse',
										'example' => 'Marie',
									],
									'nom' => [
										'type' => 'string',
										'description' => 'Le nom associé à l\'adresse',
										'example' => 'Durand',
									],
									'rue' => [
										'type' => 'string',
										'description' => 'La rue ou l\'adresse complète',
										'example' => '45 Boulevard Haussmann',
									],
									'batiment' => [
										'type' => 'string',
										'description' => 'Informations supplémentaires sur le bâtiment',
										'example' => 'Bâtiment A',
									],
									'appartement' => [
										'type' => 'string',
										'description' => 'Numéro ou informations sur l\'appartement',
										'example' => 'Appartement 12',
									],
									'code_postal' => [
										'type' => 'string',
										'description' => 'Le code postal de l\'adresse',
										'example' => '75009',
									],
									'ville' => [
										'type' => 'string',
										'description' => 'La ville associée à l\'adresse',
										'example' => 'Paris',
									],
									'pays' => [
										'type' => 'string',
										'description' => 'Le pays de l\'adresse',
										'example' => 'France',
									],
									'telephone' => [
										'type' => 'string',
										'description' => 'Le numéro de téléphone associé',
										'example' => '+33123456789',
									],
									'similaire' => [
										'type' => 'boolean',
										'description' => 'Indique si cette adresse est similaire à une autre adresse déjà existante',
										'example' => false,
									],
								],
							],
						],
					],
				],
				'responses' => [
					'200' => [
						'description' => 'Adresse modifiée avec succès',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'est ni propriétaire de l\'adresse ni administrateur',
					],
					'404' => [
						'description' => 'Adresse non trouvée',
					],
				],
			]
		),
		// Suppression d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)",
			openapiContext: [
				'summary' => 'Supprime une adresse existante',
				'description' => 'Permet de supprimer une adresse existante. Accessible uniquement à l\'administrateur ou à l\'utilisateur propriétaire.',
				'responses' => [
					'204' => [
						'description' => 'Adresse supprimée avec succès',
					],
					'403' => [
						'description' => 'Accès refusé si l\'utilisateur n\'est ni propriétaire de l\'adresse ni administrateur',
					],
					'404' => [
						'description' => 'Adresse non trouvée',
					],
				],
			]
		),
		// Création d'une nouvelle adresse (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			processor: AdresseProcessor::class,
			openapiContext: [
				'summary' => 'Crée une nouvelle adresse',
				'description' => 'Permet de créer une nouvelle adresse associée à un utilisateur.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'utilisateur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'L\'IRI de l\'utilisateur associé',
										'example' => '/api/utilisateurs/1',
									],
									'type' => [
										'type' => 'string',
										'description' => 'Le type d\'adresse (Facturation ou Livraison)',
										'example' => 'Facturation',
									],
									'prenom' => [
										'type' => 'string',
										'description' => 'Le prénom associé à l\'adresse',
										'example' => 'Jean',
									],
									'nom' => [
										'type' => 'string',
										'description' => 'Le nom associé à l\'adresse',
										'example' => 'Dupont',
									],
									'rue' => [
										'type' => 'string',
										'description' => 'La rue ou l\'adresse complète',
										'example' => '123 Rue Principale',
									],
									'batiment' => [
										'type' => 'string',
										'description' => 'Informations supplémentaires sur le bâtiment',
										'example' => 'Bâtiment B',
									],
									'appartement' => [
										'type' => 'string',
										'description' => 'Numéro ou informations sur l\'appartement',
										'example' => 'Appartement 3',
									],
									'code_postal' => [
										'type' => 'string',
										'description' => 'Le code postal de l\'adresse',
										'example' => '75001',
									],
									'ville' => [
										'type' => 'string',
										'description' => 'La ville associée à l\'adresse',
										'example' => 'Paris',
									],
									'pays' => [
										'type' => 'string',
										'description' => 'Le pays de l\'adresse',
										'example' => 'France',
									],
									'telephone' => [
										'type' => 'string',
										'description' => 'Le numéro de téléphone associé',
										'example' => '+33123456789',
									],
									'similaire' => [
										'type' => 'boolean',
										'description' => 'Indique si cette adresse est similaire à une autre adresse déjà existante',
										'example' => false,
									],
								],
								'required' => ['utilisateur', 'type', 'prenom', 'nom', 'rue', 'code_postal', 'ville', 'pays'],
							],
						],
					],
				],
				'responses' => [
					'201' => [
						'description' => 'Adresse créée avec succès',
					],
					'400' => [
						'description' => 'Erreur de validation',
					],
				],
			]
		),
	]
)]
#[ORM\Entity(repositoryClass: AdresseRepository::class)]
#[ORM\Index(name: 'idx_utilisateur_id', columns: ['utilisateur_id'])]
class Adresse
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['adresse:read'])]
	private ?int $id_adresse = null;

	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'adresses')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?Utilisateur $utilisateur = null;

	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "Le type d'adresse est obligatoire.")]
	#[Assert\Choice(choices: ["Facturation", "Livraison"], message: "Le type d'adresse doit être 'Facturation' ou 'Livraison'.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $type = null;

	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le prénom est obligatoire.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $prenom = null;

	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $nom = null;

	#[ORM\Column(type: 'string', length: 255)]
	#[Assert\NotBlank(message: "La rue est obligatoire.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $rue = null;

	#[ORM\Column(type: 'string', length: 100, nullable: true)]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $batiment = null;

	#[ORM\Column(type: 'string', length: 100, nullable: true)]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $appartement = null;

	#[ORM\Column(type: 'string', length: 5)]
	#[Assert\NotBlank(message: "Le code postal est obligatoire.")]
	#[Assert\Length(max: 5, maxMessage: "Le code postal ne peut pas dépasser {{ limit }} caractères.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $code_postal = null;

	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "La ville est obligatoire.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $ville = null;

	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le pays est obligatoire.")]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $pays = null;

	#[ORM\Column(type: 'string', length: 14, nullable: true)]
	#[Assert\Length(max: 14, maxMessage: "Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.")]
	#[Assert\Regex(
		pattern: "/^\+?[1-9]\d{1,14}$|^(0|\+33)[1-9](\s?\d{2}){4}$/",
		message: "Le numéro de téléphone n'est pas valide."
	)]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?string $telephone = null;

	#[ORM\Column(type: 'boolean', options: ['default' => false])]
	#[Groups(['adresse:read', 'adresse:write'])]
	private ?bool $similaire = false;

	// Getters et Setters...

	public function getIdAdresse(): ?int
	{
		return $this->id_adresse;
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

	public function getType(): ?string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;
		return $this;
	}

	public function getPrenom(): ?string
	{
		return $this->prenom;
	}

	public function setPrenom(string $prenom): self
	{
		$this->prenom = $prenom;
		return $this;
	}

	public function getNom(): ?string
	{
		return $this->nom;
	}

	public function setNom(string $nom): self
	{
		$this->nom = $nom;
		return $this;
	}

	public function getRue(): ?string
	{
		return $this->rue;
	}

	public function setRue(string $rue): self
	{
		$this->rue = $rue;
		return $this;
	}

	public function getBatiment(): ?string
	{
		return $this->batiment;
	}

	public function setBatiment(?string $batiment): self
	{
		$this->batiment = $batiment;
		return $this;
	}

	public function getAppartement(): ?string
	{
		return $this->appartement;
	}

	public function setAppartement(?string $appartement): self
	{
		$this->appartement = $appartement;
		return $this;
	}

	public function getCodePostal(): ?string
	{
		return $this->code_postal;
	}

	public function setCodePostal(string $code_postal): self
	{
		$this->code_postal = $code_postal;
		return $this;
	}

	public function getVille(): ?string
	{
		return $this->ville;
	}

	public function setVille(string $ville): self
	{
		$this->ville = $ville;
		return $this;
	}

	public function getPays(): ?string
	{
		return $this->pays;
	}

	public function setPays(string $pays): self
	{
		$this->pays = $pays;
		return $this;
	}

	public function getTelephone(): ?string
	{
		return $this->telephone;
	}

	public function setTelephone(?string $telephone): self
	{
		$this->telephone = $telephone;
		return $this;
	}

	public function isSimilaire(): ?bool
	{
		return $this->similaire;
	}

	public function setSimilaire(bool $similaire): self
	{
		$this->similaire = $similaire;
		return $this;
	}
}
