<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: AdresseRepository::class)]
class Adresse
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_adresse = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'adresses')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	private ?Utilisateur $utilisateur = null;

	// Type d'adresse (Facturation ou Livraison)
	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "Le type d'adresse est obligatoire.")]
	#[Assert\Choice(choices: ["Facturation", "Livraison"], message: "Le type d'adresse doit être 'Facturation' ou 'Livraison'.")]
	private ?string $type = null;

	// Prénom de l'utilisateur
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le prénom est obligatoire.")]
	private ?string $prenom = null;

	// Nom de l'utilisateur
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	private ?string $nom = null;

	// Rue de l'adresse
	#[ORM\Column(type: 'string', length: 255)]
	#[Assert\NotBlank(message: "La rue est obligatoire.")]
	private ?string $rue = null;

	// Bâtiment (optionnel)
	#[ORM\Column(type: 'string', length: 100, nullable: true)]
	private ?string $batiment = null;

	// Appartement (optionnel)
	#[ORM\Column(type: 'string', length: 100, nullable: true)]
	private ?string $appartement = null;

	// Code postal
	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "Le code postal est obligatoire.")]
	private ?string $code_postal = null;

	// Ville
	#[ORM\Column(type: 'string', length: 100)]
	#[Assert\NotBlank(message: "La ville est obligatoire.")]
	private ?string $ville = null;

	// Pays
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le pays est obligatoire.")]
	private ?string $pays = null;

	// Téléphone (optionnel)
	#[ORM\Column(type: 'string', length: 20, nullable: true)]
	private ?string $telephone = null;

	// Indicateur d'adresse similaire
	#[ORM\Column(type: 'boolean', options: ['default' => false])]
	private ?bool $similaire = false;

	// Getters et Setters

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
