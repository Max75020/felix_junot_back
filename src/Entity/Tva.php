<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\TvaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
	normalizationContext: ['groups' => ['tva:read']],
	denormalizationContext: ['groups' => ['tva:write']],
	operations: [
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
#[ORM\Index(name: 'idx_taux', columns: ['taux'])]
class Tva
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	#[Groups(['tva:read'])]
	private ?int $id_tva = null;

	// Taux de TVA avec validation supplémentaire pour s'assurer qu'il est dans une plage raisonnable
	#[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
	#[Assert\NotBlank(message: "Le taux de TVA est obligatoire.")]
	#[Assert\PositiveOrZero(message: "Le taux de TVA doit être un nombre positif ou zéro.")]
	#[Assert\Range(min: 0, max: 100, notInRangeMessage: "Le taux de TVA doit être compris entre 0 et 100.")]
	#[Groups(['tva:read', 'tva:write'])]
	private ?string $taux = null;

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
}
