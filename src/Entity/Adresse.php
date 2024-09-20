<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\State\AdresseProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['adresse:read']],
	denormalizationContext: ['groups' => ['adresse:write']],
	operations: [
		// Récupération d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)"
		),
		// Modification complète d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Put(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)"
		),
		// Modification partielle d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Patch(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)"
		),
		// Suppression d'une adresse (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(
			security: "is_granted('ROLE_ADMIN') or (is_granted('ROLE_USER') and object.getUtilisateur() == user)"
		),
		// Création d'une nouvelle adresse (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(
			security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
			processor: AdresseProcessor::class
		)
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

	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "Le code postal est obligatoire.")]
	#[Assert\Length(max: 20, maxMessage: "Le code postal ne peut pas dépasser {{ limit }} caractères.")]
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

	#[ORM\Column(type: 'string', length: 20, nullable: true)]
	#[Assert\Length(max: 20, maxMessage: "Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.")]
	#[Assert\Regex(
		pattern: "/^[+0-9\s\-\(\)]+$/",
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
