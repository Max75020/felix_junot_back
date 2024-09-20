<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\PanierRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\State\PanierProcessor;

#[ApiResource(
	normalizationContext: ['groups' => ['panier:read']],
	denormalizationContext: ['groups' => ['panier:write']],
	operations: [
		// Récupération du panier (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Get(security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user"),

		// Modification du panier (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Put(security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user"),

		// Suppression du panier (accessible à l'utilisateur propriétaire ou à l'administrateur)
		new Delete(security: "is_granted('ROLE_ADMIN') or object.getUtilisateur() == user"),

		// Création d'un panier (accessible aux utilisateurs connectés et aux administrateurs)
		new Post(security: "is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",processor: PanierProcessor::class
		)
	]
)]
#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ORM\Index(name: 'idx_utilisateur_id', columns: ['utilisateur_id'])]
class Panier
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['panier:read'])]
	private ?int $id_panier = null;

	// Relation ManyToOne avec l'entité Utilisateur
	#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'paniers')]
	#[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id_utilisateur', nullable: false)]
	#[Assert\NotBlank(message: "L'utilisateur est obligatoire.")]
	#[Groups(['panier:read', 'panier:write'])]
	private ?Utilisateur $utilisateur = null;

	// Getters et Setters

	public function getIdPanier(): ?int
	{
		return $this->id_panier;
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
}
