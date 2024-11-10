<?php

namespace App\Command;

use App\Entity\ImageProduit;
use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(name: 'app:load-product-images')]
class LoadProductImagesCommand extends Command
{
	private $entityManager;
	private $filesystem;
	private $projectDir;

	public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel)
	{
		parent::__construct();
		$this->entityManager = $entityManager;
		$this->filesystem = new Filesystem();
		$this->projectDir = $kernel->getProjectDir(); // Récupère le chemin racine du projet
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// Définition du chemin vers le dossier des images des produits
		$productImagesPath = $this->projectDir . '/public/images/produits';

		// Message de débogage pour vérifier le chemin
		$output->writeln("Chemin des images : " . $productImagesPath);

		if (!$this->filesystem->exists($productImagesPath)) {
			$output->writeln("Le dossier des images n'existe pas.");
			return Command::FAILURE;
		}

		// Récupérer tous les produits en BDD
		$produits = $this->entityManager->getRepository(Produit::class)->findAll();

		foreach ($produits as $produit) {
			// Normalisation du nom du produit pour correspondre au nom du fichier
			$productFolderName = $this->normalizeName($produit->getNom());

			// Chemin du dossier du produit (même dossier pour tous les produits)
			$productFolder = $productImagesPath . '/' . $productFolderName;

			// Affichage pour débogage
			$output->writeln("Produit : {$produit->getNom()} -> Dossier : {$productFolder}");

			if ($this->filesystem->exists($productFolder)) {
				$images = scandir($productFolder);

				$position = 0; // Position de chaque image
				foreach ($images as $image) {
					if (in_array(pathinfo($image, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'webp','JPG','HEIC'])) {
						$imageProduit = new ImageProduit();
						$imageProduit->setChemin("images/produits/{$productFolderName}/{$image}");
						$imageProduit->setProduit($produit);
						$imageProduit->setPosition($position);
						$imageProduit->setLegend('Image du produit ' . $produit->getNom());
						$imageProduit->setCover($position === 0); // La première image sera la couverture

						$this->entityManager->persist($imageProduit);
						$position++;
					}
				}
			} else {
				$output->writeln("Dossier pour le produit {$produit->getNom()} introuvable.");
			}
		}

		$this->entityManager->flush();
		$output->writeln("Images des produits chargées en base de données.");

		return Command::SUCCESS;
	}

	private function normalizeName(string $name): string
	{
		$name = str_replace(' ', '_', $name); // Remplace les espaces par des underscores
		$name = iconv('UTF-8', 'ASCII//TRANSLIT', $name); // Supprime les accents
		$name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name); // Supprime les caractères spéciaux restants
		return strtolower($name); // Mettre tout en minuscules pour uniformiser
	}
}
