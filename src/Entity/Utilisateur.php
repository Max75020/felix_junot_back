<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ApiResource]
class Utilisateur
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_utilisateur = null;

	// Prénom de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le prénom est obligatoire.")]
	private ?string $prenom = null;

	// Nom de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	private ?string $nom = null;

	// Email de l'utilisateur, unique et format valide
	#[ORM\Column(type: 'string', length: 100, unique: true)]
	#[Assert\NotBlank(message: "L'email est obligatoire.")]
	#[Assert\Email(message: "L'email n'est pas valide.")]
	private ?string $email = null;

	// Téléphone de l'utilisateur, optionnel
	#[ORM\Column(type: 'string', length: 20, nullable: true)]
	private ?string $telephone = null;

	// Mot de passe de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 255)]
	#[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
	private ?string $mot_de_passe = null;

	// Rôle de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "Le rôle est obligatoire.")]
	private ?string $role = null;

	// Token de réinitialisation du mot de passe, optionnel
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	private ?string $token_reinitialisation = null;

	// Relations avec d'autres entités
	#[ORM\OneToMany(targetEntity: Adresse::class, mappedBy: 'utilisateur')]
	private Collection $adresses;

	#[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'utilisateur')]
	private Collection $commandes;

	#[ORM\OneToMany(targetEntity: Panier::class, mappedBy: 'utilisateur')]
	private Collection $paniers;

	#[ORM\OneToMany(targetEntity: Favoris::class, mappedBy: 'utilisateur')]
	private Collection $favoris;

	#[ORM\Column(type: 'boolean')]
	private ?bool $email_valide = false;

	// Constructeur pour initialiser les collections
	public function __construct()
	{
		$this->adresses = new ArrayCollection();
		$this->commandes = new ArrayCollection();
		$this->paniers = new ArrayCollection();
		$this->favoris = new ArrayCollection();
	}

	// Getters et Setters pour toutes les propriétés

	public function getIdUtilisateur(): ?int
	{
		return $this->id_utilisateur;
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

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail(string $email): self
	{
		$this->email = $email;
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

	public function getMotDePasse(): ?string
	{
		return $this->mot_de_passe;
	}

	public function setMotDePasse(string $mot_de_passe): self
	{
		$this->mot_de_passe = $mot_de_passe;
		return $this;
	}

	public function getRole(): ?string
	{
		return $this->role;
	}

	public function setRole(string $role): self
	{
		$this->role = $role;
		return $this;
	}

	public function getTokenReinitialisation(): ?string
	{
		return $this->token_reinitialisation;
	}

	public function setTokenReinitialisation(?string $token_reinitialisation): self
	{
		$this->token_reinitialisation = $token_reinitialisation;
		return $this;
	}

	public function getAdresses(): Collection
	{
		return $this->adresses;
	}

	public function addAdresse(Adresse $adresse): self
	{
		if (!$this->adresses->contains($adresse)) {
			$this->adresses[] = $adresse;
			$adresse->setUtilisateur($this);
		}

		return $this;
	}

	public function removeAdresse(Adresse $adresse): self
	{
		if ($this->adresses->removeElement($adresse)) {
			// dissocier l'utilisateur de l'adresse si c'était lié
			if ($adresse->getUtilisateur() === $this) {
				$adresse->setUtilisateur(null);
			}
		}

		return $this;
	}

	public function getCommandes(): Collection
	{
		return $this->commandes;
	}

	public function addCommande(Commande $commande): self
	{
		if (!$this->commandes->contains($commande)) {
			$this->commandes[] = $commande;
			$commande->setUtilisateur($this);
		}

		return $this;
	}

	public function removeCommande(Commande $commande): self
	{
		if ($this->commandes->removeElement($commande)) {
			// dissocier l'utilisateur de la commande si c'était lié
			if ($commande->getUtilisateur() === $this) {
				$commande->setUtilisateur(null);
			}
		}

		return $this;
	}

	public function getPaniers(): Collection
	{
		return $this->paniers;
	}

	public function addPanier(Panier $panier): self
	{
		if (!$this->paniers->contains($panier)) {
			$this->paniers[] = $panier;
			$panier->setUtilisateur($this);
		}

		return $this;
	}

	public function removePanier(Panier $panier): self
	{
		if ($this->paniers->removeElement($panier)) {
			// dissocier l'utilisateur du panier si c'était lié
			if ($panier->getUtilisateur() === $this) {
				$panier->setUtilisateur(null);
			}
		}

		return $this;
	}

	public function getFavoris(): Collection
	{
		return $this->favoris;
	}

	public function addFavoris(Favoris $favori): self
	{
		if (!$this->favoris->contains($favori)) {
			$this->favoris[] = $favori;
			$favori->setUtilisateur($this);
		}

		return $this;
	}

	public function removeFavoris(Favoris $favori): self
	{
		if ($this->favoris->removeElement($favori)) {
			// dissocier l'utilisateur du favori si c'était lié
			if ($favori->getUtilisateur() === $this) {
				$favori->setUtilisateur(null);
			}
		}

		return $this;
	}

	public function isEmailValide(): ?bool
	{
		return $this->email_valide;
	}

	public function setEmailValide(bool $email_valide): static
	{
		$this->email_valide = $email_valide;

		return $this;
	}
}
