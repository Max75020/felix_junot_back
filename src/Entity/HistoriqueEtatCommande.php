<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HistoriqueEtatCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: HistoriqueEtatCommandeRepository::class)]
class HistoriqueEtatCommande
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_historique_etat_commande = null;

	// Relation ManyToOne avec l'entité Commande
	#[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'historique_etats')]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "La commande est obligatoire.")]
	private ?Commande $commande = null;

	// Date de changement d'état de la commande
	#[ORM\Column(type: 'date')]
	#[Assert\NotBlank(message: "La date de changement d'état est obligatoire.")]
	private ?\DateTimeInterface $date_etat = null;

	// Relation ManyToOne avec l'entité EtatCommande
	#[ORM\ManyToOne(targetEntity: EtatCommande::class)]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "L'état de la commande est obligatoire.")]
	private ?EtatCommande $etat_commande = null;

	// Getters et Setters

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
