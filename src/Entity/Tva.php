<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\TvaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
	normalizationContext: ['groups' => ['tva:read']],
	denormalizationContext: ['groups' => ['tva:write']],
	operations: [
		// Récupération de tous les taux de TVA (accessible uniquement aux administrateurs)
		new GetCollection(security: "is_granted('ROLE_ADMIN')"),

		// Récupération d'un taux de TVA (accessible à tous)
		new Get(),

		// Modification d'un taux de TVA (accessible uniquement aux administrateurs)
		new Put(security: "is_granted('ROLE_ADMIN')"),

		// Suppression d'un taux de TVA (accessible uniquement aux administrateurs)
		new Delete(security: "is_granted('ROLE_ADMIN')"),

		// Création d'un taux de TVA (accessible uniquement aux administrateurs)
		new Post(security: "is_granted('ROLE_ADMIN')")
	]
)]
#[ORM\Entity(repositoryClass: TvaRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_taux', columns: ['taux'])]
class Tva
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['tva:read'])]
	private ?int $id_tva = null;

	// Taux de TVA, maintenant de type decimal avec une précision de 5 et une échelle de 2
	#[ORM\Column(type: 'decimal', precision: 5, scale: 2, unique: true)]
	#[Assert\NotBlank(message: "Le taux de TVA est obligatoire.")]
	#[Assert\PositiveOrZero(message: "Le taux de TVA doit être un nombre positif ou zéro.")]
	#[Assert\Range(min: 0, max: 100, notInRangeMessage: "Le taux de TVA doit être compris entre 0 et 100.")]
	#[Groups(['tva:read', 'tva:write'])]
	private ?string $taux = null;

	#[ORM\OneToMany(mappedBy: 'tva', targetEntity: Produit::class)]
	private Collection $produits;

	public function __construct()
	{
		$this->produits = new ArrayCollection();
	}

	// Getters et Setters...

	public function getIdTva(): ?int
	{
		return $this->id_tva;
	}

	public function getTaux(): ?string
	{
		return $this->taux;
	}

	public function setTaux(string $taux): self
	{
		$this->taux = $taux;
		return $this;
	}

	public function getProduits(): Collection
	{
		return $this->produits;
	}

	public function addProduit(Produit $produit): self
	{
		if (!$this->produits->contains($produit)) {
			$this->produits[] = $produit;
			$produit->setTva($this);
		}

		return $this;
	}

	public function removeProduit(Produit $produit): self
	{
		if ($this->produits->removeElement($produit)) {
			if ($produit->getTva() === $this) {
				$produit->setTva(null);
			}
		}

		return $this;
	}
}
