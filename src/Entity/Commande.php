<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_commande = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'commandes')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	private ?Utilisateur $utilisateur = null;

	// Date de la commande
	#[ORM\Column(type: 'date')]
	#[Assert\NotBlank(message: "La date de commande est obligatoire.")]
	private ?\DateTimeInterface $date_commande = null;

	// Total de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le total est obligatoire.")]
	#[Assert\Positive(message: "Le total doit être positif.")]
	private ?float $total = null;

	// Relation ManyToOne avec l'entité EtatCommande
	#[ORM\ManyToOne(targetEntity: EtatCommande::class, inversedBy: 'commandes')]
	#[ORM\JoinColumn(name: 'etat_commande_id', referencedColumnName: 'id_etat_commande', nullable: false)]
	private ?EtatCommande $etat_commande = null;

	// Transporteur de la commande
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le nom du transporteur est obligatoire.")]
	private ?string $transporteur = null;

	// Poids de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Le poids est obligatoire.")]
	#[Assert\PositiveOrZero(message: "Le poids ne peut pas être négatif.")]
	private ?float $poids = null;

	// Frais de livraison de la commande
	#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
	#[Assert\NotBlank(message: "Les frais de livraison sont obligatoires.")]
	#[Assert\PositiveOrZero(message: "Les frais de livraison ne peuvent pas être négatifs.")]
	private ?float $frais_livraison = null;

	// Numéro de suivi de la commande
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "Le numéro de suivi est obligatoire.")]
	private ?string $numero_suivi = null;

	// Getters et Setters

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

	public function getTransporteur(): ?string
	{
		return $this->transporteur;
	}

	public function setTransporteur(string $transporteur): self
	{
		$this->transporteur = $transporteur;
		return $this;
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
}
