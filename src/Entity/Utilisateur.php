<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\State\UserPasswordHasher;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ApiResource(
	normalizationContext: ['groups' => ['user:read']],
	denormalizationContext: ['groups' => ['user:write']],
	operations: [

		// Récupération de tous les utilisateurs (accessible uniquement à l'administrateur)
		new GetCollection(security: "is_granted('ROLE_ADMIN')"),

		// Récupération d'un utilisateur (accessible uniquement à l'utilisateur lui-même ou à l'administrateur)
		new Get(
			security: "is_granted('ROLE_USER') and object == user or is_granted('ROLE_ADMIN')"
		),
		// Modification complète d'un utilisateur (accessible uniquement à l'utilisateur lui-même ou à l'administrateur)
		new Put(
			security: "is_granted('ROLE_USER') and object == user or is_granted('ROLE_ADMIN')",
			processor: UserPasswordHasher::class
		),
		// Modification partielle d'un utilisateur (PATCH, accessible uniquement à l'utilisateur lui-même ou à l'administrateur)
		new Patch(
			security: "is_granted('ROLE_USER') and object == user or is_granted('ROLE_ADMIN')",
			processor: UserPasswordHasher::class
		),
		// Suppression d'un utilisateur (accessible uniquement à l'utilisateur lui-même ou à l'administrateur)
		new Delete(
			security: "is_granted('ROLE_USER') and object == user or is_granted('ROLE_ADMIN')"
		),
		// Création d'un nouvel utilisateur (accessible à tous)
		new Post(
			processor: UserPasswordHasher::class
		)
	]
)]
#[UniqueEntity(
	fields: ['email'],
	message: 'Cet email est déjà utilisé.'
)]
#[ORM\Index(name: 'idx_role', columns: ['role'])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['user:read','adresse:read'])]
	private ?int $id_utilisateur = null;

	// Prénom de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le prénom est obligatoire.")]
	#[Groups(['user:read', 'user:write'])]
	private ?string $prenom = null;

	// Nom de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 50)]
	#[Assert\NotBlank(message: "Le nom est obligatoire.")]
	#[Groups(['user:read', 'user:write'])]
	private ?string $nom = null;

	// Email de l'utilisateur, unique et format valide
	#[ORM\Column(type: 'string', length: 100, unique: true)]
	#[Assert\NotBlank(message: "L'email est obligatoire.")]
	#[Assert\Email(message: "L'email n'est pas valide.")]
	#[Assert\Length(max: 100, maxMessage: "L'email ne peut pas dépasser 100 caractères.")]
	#[Groups(['user:read', 'user:write'])]
	private ?string $email = null;

	// Téléphone de l'utilisateur, optionnel
	#[ORM\Column(type: 'string', length: 14, nullable: true)]
	#[Assert\Length(max: 14, maxMessage: "Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.")]
	#[Assert\Regex(
		pattern: "/^\+?[1-9]\d{1,14}$|^(0|\+33)[1-9](\s?\d{2}){4}$/",
		message: "Le numéro de téléphone n'est pas valide."
	)]
	#[Groups(['user:read', 'user:write'])]
	private ?string $telephone = null;

	// Mot de passe de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 255)]
	#[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
	#[Assert\Length(min: 12, minMessage: "Le mot de passe doit comporter au moins {{ limit }} caractères.")]
	#[Assert\Regex(pattern: "/[A-Z]/", message: "Le mot de passe doit contenir au moins une lettre majuscule.")]
	#[Assert\Regex(pattern: "/[a-z]/", message: "Le mot de passe doit contenir au moins une lettre minuscule.")]
	#[Assert\Regex(pattern: "/[0-9]/", message: "Le mot de passe doit contenir au moins un chiffre.")]
	#[Assert\Regex(pattern: "/[\W]/", message: "Le mot de passe doit contenir au moins un caractère spécial.")]
	#[Groups(['user:write'])]
	private ?string $password = null;

	// Rôle de l'utilisateur, ne doit pas être vide
	#[ORM\Column(type: 'string', length: 20)]
	#[Assert\NotBlank(message: "Le rôle est obligatoire.")]
	#[Groups(['user:read', 'user:write'])]
	private ?string $role = 'ROLE_USER';

	// Token de réinitialisation du mot de passe, optionnel
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	#[Groups(['user:write'])]
	private ?string $token_reinitialisation = null;

	#[ORM\Column(type: 'boolean', options: ['default' => false])]
	#[Groups(['user:read', 'user:write'])]
	private ?bool $email_valide = false;

	// Relations avec d'autres entités
	#[ORM\OneToMany(targetEntity: Adresse::class, mappedBy: 'utilisateur')]
	#[Groups(['user:read'])]
	private Collection $adresses;

	#[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'utilisateur')]
	#[Groups(['user:read'])]
	private Collection $commandes;

	#[ORM\OneToMany(targetEntity: Panier::class, mappedBy: 'utilisateur')]
	#[Groups(['user:read'])]
	private Collection $paniers;

	#[ORM\OneToMany(targetEntity: Favoris::class, mappedBy: 'utilisateur')]
	#[Groups(['user:read'])]
	private Collection $favoris;


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

	public function getPassword(): ?string
	{
		// Retourne le champ 'password' pour Symfony
		return $this->password;
	}

	public function setPassword(string $password): self
	{
		// Définit le champ 'password'
		$this->password = $password;
		return $this;
	}

	public function getRole(): ?string
	{
		return $this->role;
	}

	public function setRole(string $role): self
	{
		if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
			throw new \InvalidArgumentException('Rôle invalide.');
		}

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

	// Méthodes de l'interface UserInterface

	public function getUserIdentifier(): string
	{
		return $this->email;
	}

	public function getUsername(): string
	{
		return $this->email;
	}

	// Adapter la méthode getRoles() pour utiliser le champ 'role' existant car Symfony attend un tableau de rôles et non une simple chaîne de caractères
	public function getRoles(): array
	{
		// Toujours inclure 'ROLE_USER' pour garantir un rôle minimum
		$roles = [$this->role];

		if (!in_array('ROLE_USER', $roles)) {
			$roles[] = 'ROLE_USER';
		}

		return array_unique($roles);
	}


	public function eraseCredentials(): void
	{
		// Dans mon cas, je n'ai pas besoin de cette méthode mais elle est obligatoire avec l'interface UserInterface, je la laisse vide
	}

	public function getSalt(): ?string
	{
		// Non nécessaire si bcrypt ou argon2i est utilisé
		return null;
	}
}
