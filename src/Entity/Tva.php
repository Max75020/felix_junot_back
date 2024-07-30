<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TvaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource]
#[ORM\Entity(repositoryClass: TvaRepository::class)]
class Tva
{
	// Clé primaire avec auto-incrémentation
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id_tva = null;

	// Taux de TVA
	#[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
	#[Assert\NotBlank(message: "Le taux de TVA est obligatoire.")]
	#[Assert\PositiveOrZero(message: "Le taux de TVA doit être un nombre positif ou zéro.")]
	private ?float $taux = null;

	// Getters et Setters

	public function getIdTva(): ?int
	{
		return $this->id_tva;
	}

	public function getTaux(): ?float
	{
		return $this->taux;
	}

	public function setTaux(float $taux): self
	{
		$this->taux = $taux;
		return $this;
	}
}
