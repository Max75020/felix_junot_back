<?php

namespace App\State;

use App\Entity\PanierProduit;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor pour gérer les opérations sur l'entité PanierProduit.
 * Il vérifie le stock, met à jour la quantité et calcule le prix total du produit dans le panier.
 */
class PanierProduitProcessor implements ProcessorInterface
{
	private Security $security;
	private EntityManagerInterface $entityManager;

	public function __construct(Security $security, EntityManagerInterface $entityManager)
	{
		$this->security = $security;
		$this->entityManager = $entityManager;
	}

	public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
	{
		// Vérifier si les données sont bien une instance de PanierProduit
		if (!$data instanceof PanierProduit) {
			return $data; // Retourner les données sans traitement si ce n'est pas un PanierProduit
		}

		$utilisateur = $this->security->getUser();

		// Assigner l'utilisateur connecté au panier si ce n'est pas déjà fait
		if ($data->getPanier()->getUtilisateur() === null) {
			$data->getPanier()->setUtilisateur($utilisateur);
		}

		$produit = $data->getProduit();
		$quantiteDemandee = $data->getQuantite();

		// Vérifier le stock disponible du produit
		if ($produit->getStock() <= 0) {
			throw new BadRequestHttpException('Le produit est en rupture de stock.');
		}

		if ($quantiteDemandee > $produit->getStock()) {
			throw new BadRequestHttpException('La quantité demandée dépasse le stock disponible.');
		}

		// Rechercher si le produit existe déjà dans le panier
		$existingPanierProduit = $this->entityManager->getRepository(PanierProduit::class)
			->findOneBy(['panier' => $data->getPanier(), 'produit' => $data->getProduit()]);

		if ($existingPanierProduit) {
			// Si le produit existe déjà, mettre à jour la quantité et recalculer le prix total
			$newQuantite = $existingPanierProduit->getQuantite() + $quantiteDemandee;

			if ($newQuantite > $produit->getStock()) {
				throw new BadRequestHttpException('La quantité totale dans le panier dépasse le stock disponible.');
			}

			$existingPanierProduit->setQuantite($newQuantite);
			$existingPanierProduit->recalculatePrixTotalProduit(); // Utiliser la méthode de l'entité pour recalculer le prix total
		} else {
			// Sinon, créer une nouvelle entrée de produit dans le panier et calculer son prix total
			$data->recalculatePrixTotalProduit(); // Calculer le prix total du produit directement via la méthode de l'entité
			$this->entityManager->persist($data);
		}

		// Enregistrer les modifications dans la base de données
		$this->entityManager->flush();

		return $data;
	}
}
