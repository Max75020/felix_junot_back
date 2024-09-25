<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\ImageProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
	normalizationContext: ['groups' => ['imageProduit:read']],
	denormalizationContext: ['groups' => ['imageProduit:write']],
	operations: [
		// Récupération de toutes les images (accessible à tous)
		new GetCollection(),

		// Récupération d'une image (accessible à tous)
		new Get(),

		// Modification d'une image (accessible uniquement aux administrateurs)
		new Put(security: "is_granted('ROLE_ADMIN')"),

		// Suppression d'une image (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'une nouvelle image (accessible uniquement aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN')")
	]
)]
#[ORM\Entity(repositoryClass: ImageProduitRepository::class)]
#[ORM\Index(name: 'idx_produit_id', columns: ['produit_id'])]
class ImageProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['imageProduit:read'])]
	private ?int $id_image_produit = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'images')]
	#[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id_produit', nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	#[Groups(['imageProduit:read', 'imageProduit:write'])]
	private ?Produit $produit = null;

	// Position de l'image dans la liste des images du produit
	#[ORM\Column(type: 'integer', options: ['default' => 0])]
	#[Assert\NotBlank(message: "La position est obligatoire.")]
	#[Assert\PositiveOrZero(message: "La position doit être un nombre positif ou zéro.")]
	#[Groups(['imageProduit:read', 'imageProduit:write'])]
	private ?int $position = 0;

	// Indique si l'image est la couverture du produit
	#[ORM\Column(type: 'boolean', options: ['default' => false])]
	#[Groups(['imageProduit:read', 'imageProduit:write'])]
	private ?bool $cover = false;

	// Légende de l'image
	#[ORM\Column(type: 'string', length: 128)]
	#[Assert\NotBlank(message: "La légende est obligatoire.")]
	#[Assert\Length(max: 128, maxMessage: "La légende ne peut pas dépasser 128 caractères.")]
	#[Groups(['imageProduit:read', 'imageProduit:write'])]
	private ?string $legend = null;

	// Getters et Setters...

	public function getIdImageProduit(): ?int
	{
		return $this->id_image_produit;
	}

	public function getProduit(): ?Produit
	{
		return $this->produit;
	}

	public function setProduit(?Produit $produit): self
	{
		$this->produit = $produit;
		return $this;
	}

	public function getPosition(): ?int
	{
		return $this->position;
	}

	public function setPosition(int $position): self
	{
		$this->position = $position;
		return $this;
	}

	public function getCover(): ?bool
	{
		return $this->cover;
	}

	public function setCover(bool $cover): self
	{
		$this->cover = $cover;
		return $this;
	}

	public function getLegend(): ?string
	{
		return $this->legend;
	}

	public function setLegend(string $legend): self
	{
		$this->legend = $legend;
		return $this;
	}
}
