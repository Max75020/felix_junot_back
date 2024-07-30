<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ImageProduitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: ImageProduitRepository::class)]
class ImageProduit
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_image_produit = null;

	// Relation ManyToOne avec l'entité Produit
	#[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'images')]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\NotBlank(message: "Le produit est obligatoire.")]
	private ?Produit $produit = null;

	// Position de l'image dans la liste des images du produit
	#[ORM\Column(type: 'integer', options: ['default' => 0])]
	#[Assert\NotBlank(message: "La position est obligatoire.")]
	private ?int $position = 0;

	// Indique si l'image est la couverture du produit
	#[ORM\Column(type: 'boolean', options: ['default' => false])]
	private ?bool $cover = false;

	// Légende de l'image
	#[ORM\Column(type: 'string', length: 128)]
	#[Assert\NotBlank(message: "La légende est obligatoire.")]
	#[Assert\Length(max: 128, maxMessage: "La légende ne peut pas dépasser 128 caractères.")]
	private ?string $legend = null;

	// Getters et Setters

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
