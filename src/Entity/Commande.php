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
									'prix_total_commande' => [
										'type' => 'string',
										'description' => 'Prix total de la commande',
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
								'required' => ['utilisateur', 'prix_total_commande', 'etat_commande', 'transporteur', 'poids', 'frais_livraison', 'numero_suivi', 'reference']
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
#[ORM\Index(name: 'idx_panier_id', columns: ['panier_id'])]
#[ORM\Index(name: 'idx_adresse_facturation_id', columns: ['id_adresse_facturation'])]
#[ORM\Index(name: 'idx_adresse_livraison_id', columns: ['id_adresse_livraison'])]
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

	// Total des produits dans la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2, name: 'total_produits_commande')]
	#[Assert\NotBlank(message: "Le total des produits dans la commande ne peut pas être vide.")]
	#[Assert\Positive(message: "Le total des produits doit être positif.")]
	#[Groups(['commande:read', 'commande:write'])]
	private string $total_produits_commande = '10.00';

	// Relation ManyToOne avec l'entité EtatCommande
	#[ORM\ManyToOne(targetEntity: EtatCommande::class, inversedBy: 'commandes')]
	#[ORM\JoinColumn(name: 'etat_commande_id', referencedColumnName: 'id_etat_commande', nullable: false)]
	#[Groups(['commande:read', 'commande:write'])]
	private ?EtatCommande $etat_commande = null;

	// Relation ManyToOne avec l'entité Transporteurs
	#[ORM\ManyToOne(targetEntity: Transporteurs::class, inversedBy: 'commandes', cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'transporteur_id', referencedColumnName: 'id_transporteur', nullable: false)]
	#[Assert\NotBlank(message: "Le transporteur est obligatoire.")]
	#[Groups(['commande:read', 'commande:write'])]
	private ?Transporteurs $transporteur = null;

	// Poids de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\PositiveOrZero(message: "Le poids ne peut pas être négatif.")]
	#[Groups(['commande:read', 'commande:write'])]
	private ?string $poids = null;

	// Frais de livraison de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Les frais de livraison sont obligatoires.")]
	#[Assert\PositiveOrZero(message: "Les frais de livraison ne peuvent pas être négatifs.")]
	#[Groups(['commande:read', 'commande:write'])]
	private string $frais_livraison;

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

	// Prix total de la commande (total des produits + frais de livraison)
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2, name: 'prix_total_commande')]
	#[Assert\NotBlank(message: "Le prix total de la commande est obligatoire.")]
	#[Assert\GreaterThanOrEqual(value: 0, message: "Le prix total de la commande ne peut pas être négatif.")]
	// Le prix total de la commande est bien la somme du total produits et des frais de livraison
	#[Assert\Expression(
		"this.getPrixTotalCommande() === (this.getTotalProduitsCommande() + this.getFraisLivraison())",
		message: "Le prix total de la commande doit correspondre à la somme du total des produits et des frais de livraison."
	)]
	private string $prix_total_commande = '0.00';

	// Relation ManyToOne avec l'entité Panier
	#[ORM\ManyToOne(targetEntity: Panier::class)]
	#[ORM\JoinColumn(name: 'panier_id', referencedColumnName: 'id_panier', nullable: false)]
	private ?Panier $panier = null;

	// Relation OneToMany avec l'entité CommandeProduit
	// Cascade persist et remove pour enregistrer et supprimer automatiquement les commandes produits associées
	#[ORM\OneToMany(mappedBy: 'commande', targetEntity: CommandeProduit::class, cascade: ['persist', 'remove'])]
	private Collection $commandeProduits;

	// Relation OneToMany avec l'entité HistoriqueEtatCommande
	// Cascade persist et remove pour enregistrer et supprimer automatiquement les historiques d'états associés
	#[ORM\OneToMany(mappedBy: 'commande', targetEntity: HistoriqueEtatCommande::class, cascade: ['persist', 'remove'])]
	#[Groups(['commande:read'])]
	private Collection $historiqueEtats;

	#[ORM\ManyToOne(targetEntity: Adresse::class)]
	#[ORM\JoinColumn(name: 'id_adresse_facturation', referencedColumnName: 'id_adresse', nullable: false)]
	#[Groups(['commande:read', 'commande:write'])]
	private ?Adresse $adresseFacturation = null;

	#[ORM\ManyToOne(targetEntity: Adresse::class)]
	#[ORM\JoinColumn(name: 'id_adresse_livraison', referencedColumnName: 'id_adresse', nullable: false)]
	#[Groups(['commande:read', 'commande:write'])]
	private ?Adresse $adresseLivraison = null;

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

	public function getPoids(): ?string
	{
		return $this->poids;
	}

	public function setPoids(string $poids): self
	{
		$this->poids = $poids;
		return $this;
	}

	public function getFraisLivraison(): ?string
	{
		return $this->frais_livraison;
	}

	public function setFraisLivraison(string $frais_livraison): self
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

	/**
	 * Retourne le panier associé à cette commande.
	 *
	 * @return Panier|null Le panier associé, ou null s'il n'y en a pas.
	 */
	public function getPanier(): ?Panier
	{
		return $this->panier;
	}

	/**
	 * Définit le prix total de la commande.
	 *
	 * @param string|null $prixTotalCommande Le prix total de la commande au format string (ex: "100.00") ou null.
	 * @return self
	 */
	public function setPrixTotalCommande(?string $prixTotalCommande): self
	{
		$this->prix_total_commande = $prixTotalCommande;

		return $this;
	}

	/**
	 * Retourne le prix total de la commande.
	 *
	 * @return string|null Le prix total de la commande au format string ou null.
	 */
	public function getPrixTotalCommande(): ?string
	{
		return $this->prix_total_commande;
	}

	/**
	 * Définit le total des produits dans la commande.
	 *
	 * @param string|null $totalProduitsCommande Le total des produits dans la commande au format string (ex: "100.00") ou null.
	 * @return self
	 */
	public function setTotalProduitsCommande(?string $totalProduitsCommande): self
	{
		$this->total_produits_commande = $totalProduitsCommande;

		return $this;
	}

	/**
	 * Retourne le total des produits dans la commande.
	 *
	 * @return string|null Le total des produits dans la commande au format string ou null.
	 */
	public function getTotalProduitsCommande(): ?string
	{
		return $this->total_produits_commande;
	}

	public function getAdresseFacturation(): ?Adresse
	{
		return $this->adresseFacturation;
	}

	public function setAdresseFacturation(?Adresse $adresseFacturation): self
	{
		$this->adresseFacturation = $adresseFacturation;
		return $this;
	}

	public function getAdresseLivraison(): ?Adresse
	{
		return $this->adresseLivraison;
	}

	public function setAdresseLivraison(?Adresse $adresseLivraison): self
	{
		$this->adresseLivraison = $adresseLivraison;
		return $this;
	}
}
