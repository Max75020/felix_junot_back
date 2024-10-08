<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\CommandeProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Filter\CurrentUserFilter;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ApiResource(
	normalizationContext: ['groups' => ['commande:read']],
	denormalizationContext: ['groups' => ['commande:write']],
	operations: [

		// Récupération de toutes les commandes (accessible uniquement à l'utilisateur propriétaire ou à l'administrateur)
		new GetCollection(security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')"),

		// Récupération d'une commande (accessible uniquement à l'utilisateur propriétaire ou à l'administrateur)
		new Get(security: "is_granted('ROLE_USER') and object.getUtilisateur() == user or is_granted('ROLE_ADMIN')"),

		// Modification partielle d'une commande (accessible uniquement à l'administrateur)
		new Patch(
			security: "is_granted('ROLE_ADMIN')",
			processor: CommandeProcessor::class,
		),

		// Suppression d'une commande (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'une nouvelle commande (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			processor: CommandeProcessor::class,
			openapiContext: [
				'summary' => 'Crée une nouvelle commande.',
				'requestBody' => [
					'content' => [
						'application/json' => [
							'schema' => [
								'type' => 'object',
								'properties' => [
									'utilisateur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de l\'utilisateur',
										'example' => '/api/utilisateurs/1'
									],
									'etat_commande' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI de l\'état de la commande',
										'example' => '/api/etat_commandes/1'
									],
									'total' => [
										'type' => 'string',
										'description' => 'Total de la commande',
										'example' => '19.99'
									],
									'id_transporteur' => [
										'type' => 'string',
										'format' => 'iri',
										'description' => 'IRI du transporteur',
										'example' => '/api/transporteurs/1'
									],
									'poids' => [
										'type' => 'string',
										'description' => 'Poids de la commande en kg',
										'example' => '1.2'
									],
									'frais_livraison' => [
										'type' => 'string',
										'description' => 'Frais de livraison',
										'example' => '4.95'
									],
									'numero_suivi' => [
										'type' => 'string',
										'description' => 'Numéro de suivi du colis',
										'example' => '1234567890'
									],
									'reference' => [
										'type' => 'string',
										'description' => 'Référence unique de la commande',
										'example' => 'CMD-1-01012021120000'
									]
								],
								'required' => ['utilisateur', 'total', 'etat_commande', 'transporteur', 'poids', 'frais_livraison', 'numero_suivi', 'reference']
							]
						]
					]
				]
			]
		),
	]
)]
#[ApiFilter(CurrentUserFilter::class)]
#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Index(name: 'idx_utilisateur_id', columns: ['utilisateur_id'])]
#[ORM\Index(name: 'idx_etat_commande_id', columns: ['etat_commande_id'])]
#[ORM\Index(name: 'idx_date_commande', columns: ['date_commande'])]
#[ORM\Index(name: 'idx_transporteurs_id', columns: ['transporteur_id'])]
class Commande
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['commande:read', 'historiqueEtatCommande:read'])]
	private ?int $id_commande = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'commandes', cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	#[Groups(['commande:read', 'commande:write'])]
	private ?Utilisateur $utilisateur = null;

	// Date de la commande
	#[ORM\Column(type: 'datetime')]
	#[DateTimeNormalizer(format: 'd-m-Y H:i:s')]
	#[Assert\NotBlank(message: "La date de commande est obligatoire.")]
	#[Assert\Type(\DateTimeInterface::class, message: "La date de commande doit être une date valide.")]
	#[Groups(['commande:read'])]
	private ?\DateTimeInterface $date_commande = null;

	// Total de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le total est obligatoire.")]
	#[Assert\Positive(message: "Le total doit être positif.")]
	#[Groups(['commande:read', 'commande:write'])]
	private float $total;

	// Relation ManyToOne avec l'entité EtatCommande
	#[ORM\ManyToOne(targetEntity: EtatCommande::class, inversedBy: 'commandes')]
	#[ORM\JoinColumn(name: 'etat_commande_id', referencedColumnName: 'id_etat_commande', nullable: false)]
	#[Groups(['commande:read', 'commande:write'])]
	private ?EtatCommande $etat_commande = null;

	// Relation ManyToOne avec l'entité Transporteurs
	#[ORM\ManyToOne(targetEntity: Transporteurs::class, cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'transporteur_id', referencedColumnName: 'id_transporteur', nullable: false)]
	#[Assert\NotBlank(message: "Le transporteur est obligatoire.")]
	#[Groups(['commande:read', 'commande:write'])]
	private ?Transporteurs $transporteur = null;

	// Poids de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\PositiveOrZero(message: "Le poids ne peut pas être négatif.")]
	#[Groups(['commande:read', 'commande:write'])]
	private ?float $poids = null;

	// Frais de livraison de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Les frais de livraison sont obligatoires.")]
	#[Assert\PositiveOrZero(message: "Les frais de livraison ne peuvent pas être négatifs.")]
	#[Groups(['commande:read', 'commande:write'])]
	private float $frais_livraison;

	// Numéro de suivi de la commande
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le numéro de suivi est obligatoire.")]
	#[Assert\Length(max: 100, maxMessage: "Le numéro de suivi ne peut pas dépasser {{ limit }} caractères.")]
	#[Groups(['commande:read', 'commande:write'])]
	private ?string $numero_suivi = null;

	// Référence de la commande
	#[ORM\Column(type: 'string', length: 30)]
	#[Assert\Length(max: 30, maxMessage: "La référence doit contenir {{ limit }} caractères maximum.")]
	#[Groups(['commande:read'])]
	private ?string $reference = null;

	// Relation OneToMany avec l'entité CommandeProduit
	// Cascade persist et remove pour enregistrer et supprimer automatiquement les commandes produits associées
	#[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeProduit::class, cascade: ['persist', 'remove'])]
	private Collection $commandeProduits;

	// Relation OneToMany avec l'entité HistoriqueEtatCommande
	// Cascade persist et remove pour enregistrer et supprimer automatiquement les historiques d'états associés
	#[ORM\OneToMany(mappedBy: 'commande', targetEntity: HistoriqueEtatCommande::class, cascade: ['persist', 'remove'])]
	#[Groups(['commande:read'])]
	private Collection $historiqueEtats;

	// Constructeur pour initialiser automatiquement la date de commande et générer une référence unique
	public function __construct()
	{
		$this->commandeProduits = new ArrayCollection();
		$this->historiqueEtats = new ArrayCollection();
		// Initialise la date avec la date actuelle
		$this->date_commande = new \DateTime();

		// Génère une référence unique après avoir configuré l'utilisateur et la date de commande
		$this->generateReference();
	}

	// Getters et Setters...

	public function getIdCommande(): ?int
	{
		return $this->id_commande;
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

	public function getDateCommande(): ?\DateTimeInterface
	{
		return $this->date_commande;
	}

	public function setDateCommande(\DateTimeInterface $date_commande): self
	{
		$this->date_commande = $date_commande;
		return $this;
	}

	public function getTotal(): ?float
	{
		return $this->total;
	}

	public function setTotal(float $total): self
	{
		$this->total = $total;
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

	public function getTransporteur(): ?Transporteurs
	{
		return $this->transporteur;
	}

	public function setTransporteur(?Transporteurs $transporteur): self
	{
		$this->transporteur = $transporteur;
		return $this;
	}

	public function getNomTransporteur(): ?string
	{
		return $this->transporteur ? $this->transporteur->getNom() : null;
	}

	public function getPoids(): ?float
	{
		return $this->poids;
	}

	public function setPoids(float $poids): self
	{
		$this->poids = $poids;
		return $this;
	}

	public function getFraisLivraison(): ?float
	{
		return $this->frais_livraison;
	}

	public function setFraisLivraison(float $frais_livraison): self
	{
		$this->frais_livraison = $frais_livraison;
		return $this;
	}

	public function getNumeroSuivi(): ?string
	{
		return $this->numero_suivi;
	}

	public function setNumeroSuivi(string $numero_suivi): self
	{
		$this->numero_suivi = $numero_suivi;
		return $this;
	}

	public function getReference(): ?string
	{
		return $this->reference;
	}

	public function setReference(string $reference): self
	{
		$this->reference = $reference;
		return $this;
	}

	/**
	 *  Génère et attribue une référence unique basée sur l'ID de l'utilisateur et la date de commande
	 */
	public function generateReference()
	{
		if ($this->utilisateur && $this->date_commande) {
			// Récupère l'ID de l'utilisateur
			$userId = $this->utilisateur->getIdUtilisateur();
			// Format : jour mois année heures minutes secondes
			$date = $this->date_commande->format('dmYHis');
			// Génère la référence
			$this->reference = 'CMD-' . $userId . '-' . $date;
		}
	}

	public function getCommandeProduits(): Collection
	{
		return $this->commandeProduits;
	}

	public function addCommandeProduit(CommandeProduit $commandeProduit): self
	{
		if (!$this->commandeProduits->contains($commandeProduit)) {
			$this->commandeProduits[] = $commandeProduit;
			$commandeProduit->setCommande($this);
		}

		return $this;
	}

	public function removeCommandeProduit(CommandeProduit $commandeProduit): self
	{
		if ($this->commandeProduits->removeElement($commandeProduit)) {

			if ($commandeProduit->getCommande() === $this) {
				$commandeProduit->setCommande(null);
			}
		}

		return $this;
	}

	public function getHistoriqueEtats(): Collection
	{
		return $this->historiqueEtats;
	}

	public function addHistoriqueEtat(HistoriqueEtatCommande $historiqueEtat): self
	{
		if (!$this->historiqueEtats->contains($historiqueEtat)) {
			$this->historiqueEtats[] = $historiqueEtat;
			$historiqueEtat->setCommande($this);
		}

		return $this;
	}

	public function removeHistoriqueEtat(HistoriqueEtatCommande $historiqueEtat): self
	{
		if ($this->historiqueEtats->removeElement($historiqueEtat)) {
			if ($historiqueEtat->getCommande() === $this) {
				$historiqueEtat->setCommande(null);
			}
		}

		return $this;
	}
}
