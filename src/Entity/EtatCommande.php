<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\EtatCommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ApiResource(
	normalizationContext: ['groups' => ['etatCommande:read']],
	denormalizationContext: ['groups' => ['etatCommande:write']],
	operations: [
		// Récupération d'un état de commande (accessible à tous)
		new Get(),

		// Modification d'un état de commande (accessible uniquement aux administrateurs)
		new Put(security: "is_granted('ROLE_ADMIN')"),

		// Suppression d'un état de commande (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'un nouvel état de commande (accessible uniquement aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN')")
	]
)]
#[ORM\Entity(repositoryClass: EtatCommandeRepository::class)]
#[UniqueEntity(
	fields: ['libelle'],
	message: 'Ce libellé existe déjà.'
)]
#[ORM\Index(name: 'idx_libelle', columns: ['libelle'])]
class EtatCommande
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['etatCommande:read'])]
	private ?int $id_etat_commande = null;

	// Libellé de l'état de la commande (doit être unique)
	#[ORM\Column(type: 'string', length: 50, unique: true)]
	#[Assert\NotBlank(message: "Le libellé est obligatoire.")]
	#[Assert\Length(
		max: 50,
		maxMessage: "Le libellé ne peut pas dépasser {{ limit }} caractères."
	)]
	#[Groups(['etatCommande:read', 'etatCommande:write'])]
	private ?string $libelle = null;

	// Relation OneToMany avec l'entité Commande
	#[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'etat_commande')]
	private Collection $commandes;

	public function __construct()
	{
		$this->commandes = new ArrayCollection();
	}

	// Getters et Setters...

	public function getIdEtatCommande(): ?int
	{
		return $this->id_etat_commande;
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
