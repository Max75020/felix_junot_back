<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\HistoriqueEtatCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
	normalizationContext: ['groups' => ['historiqueEtatCommande:read']],
	denormalizationContext: ['groups' => ['historiqueEtatCommande:write']],
	operations: [
		// Récupération de l'historique d'un état de commande (accessible à l'administrateur ou à l'utilisateur propriétaire)
		new Get(security: "is_granted('ROLE_ADMIN') or object.getCommande().getUtilisateur() == user"),

		// Création d'un historique d'état de commande (accessible uniquement aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN')")
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
	#[Groups(['historiqueEtatCommande:read'])]
	private ?int $id_historique_etat_commande = null;

	// Relation ManyToOne avec l'entité Commande
	#[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'historiqueEtats')]
	#[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id_commande', nullable: false)]
	#[Assert\NotBlank(message: "La commande est obligatoire.")]
	#[Groups(['historiqueEtatCommande:read', 'historiqueEtatCommande:write'])]
	private ?Commande $commande = null;

	// Date de changement d'état de la commande
	#[ORM\Column(type: 'date')]
	#[Assert\NotBlank(message: "La date de changement d'état est obligatoire.")]
	#[Assert\Type(\DateTimeInterface::class, message: "La date de changement d'état doit être une date valide.")]
	#[Groups(['historiqueEtatCommande:read', 'historiqueEtatCommande:write'])]
	private ?\DateTimeInterface $date_etat = null;

	// Relation ManyToOne avec l'entité EtatCommande
	#[ORM\ManyToOne(targetEntity: EtatCommande::class,inversedBy: 'historiqueEtats')]
	#[ORM\JoinColumn(name: 'etat_commande_id', referencedColumnName: 'id_etat_commande', nullable: false)]
	#[Assert\NotBlank(message: "L'état de la commande est obligatoire.")]
	#[Groups(['historiqueEtatCommande:read', 'historiqueEtatCommande:write'])]
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
