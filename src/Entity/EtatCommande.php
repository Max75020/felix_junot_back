<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\EtatCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: EtatCommandeRepository::class)]
class EtatCommande
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_etat_commande = null;

	// Libellé de l'état de la commande
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le libellé est obligatoire.")]
	#[Assert\Length(
		max: 50,
		maxMessage: "Le libellé ne peut pas dépasser {{ limit }} caractères."
	)]
	private ?string $libelle = null;

	// Getters et Setters

	public function getIdEtatCommande(): ?int
	{
		return $this->id_etat_commande;
	}

	public function setIdEtatCommande(int $id_etat_commande): self
	{
		$this->id_etat_commande = $id_etat_commande;

		return $this;
	}

	public function getLibelle(): ?string
	{
		return $this->libelle;
	}

	public function setLibelle(string $libelle): self
	{
		$this->libelle = $libelle;

		return $this;
	}
}
