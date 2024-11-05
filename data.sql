-- Création des catégories
INSERT INTO
	categorie (nom)
VALUES
	('Sculptures'),
	('Objets');

-- Création d'une TVA à 20% par défaut ---
INSERT INTO
	tva (taux)
VALUES
	(20.00);

-- Création des produits pour la catégorie Sculptures
INSERT INTO
	produit (nom, prix_ht, prix_ttc, stock)
VALUES
	('aglomerats', 0.00, 0.00, 0),
	('arkhaïos 1', 0.00, 0.00, 0),
	('arkhaïos 2', 0.00, 0.00, 0),
	('arkhaïos 3', 0.00, 0.00, 0),
	('arkhaïos 4', 0.00, 0.00, 0),
	('arkhaïos 5', 0.00, 0.00, 0),
	('Cheval', 0.00, 0.00, 0),
	('Main auto destructrice', 0.00, 0.00, 0),
	(
		'parallélépipède et forme de révolutions',
		0.00,
		0.00,
		0
	),
	('Sans titre (constructions n°2)', 0.00, 0.00, 0),
	('sculptures mains berton', 0.00, 0.00, 0),
	('surchauffe', 0.00, 0.00, 0);

-- Création des produits pour la catégorie Objets
INSERT INTO
	produit (nom, prix_ht, prix_ttc, stock)
VALUES
	('tabouret 1', 0.00, 0.00, 0),
	('tabouret 2', 0.00, 0.00, 0);

-- Création du transporteur
INSERT INTO
	transporteur (nom)
VALUES
	('À l''atelier');

-- Création de la méthode de livraison
INSERT INTO
	methode_livraison (
		transporteur_id,
		nom,
		description,
		prix,
		delai_estime
	)
VALUES
	(
		(
			SELECT
				id_transporteur
			FROM
				transporteur
			WHERE
				nom = 'À l''atelier'
		),
		'Venez récupérer votre commande à l''atelier',
		'Venez récupérer en main propre vos œuvres directement à l''atelier, remis en main propre par Félix JUNOT.',
		0.00,
		NULL
	);

-- Association des produits de la catégorie Sculptures
INSERT INTO
	produit_categorie (produit_id, categorie_id)
VALUES
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'aglomerats'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'arkhaïos 1'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'arkhaïos 2'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'arkhaïos 3'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'arkhaïos 4'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'arkhaïos 5'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'Cheval'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'Main auto destructrice'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'parallélépipède et forme de révolutions'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'Sans titre (constructions n°2)'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'sculptures mains berton'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'surchauffe'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Sculptures'
		)
	);

-- Association des produits de la catégorie Objets
INSERT INTO
	produit_categorie (produit_id, categorie_id)
VALUES
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'tabouret 1'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Objets'
		)
	),
	(
		(
			SELECT
				id_produit
			FROM
				produit
			WHERE
				nom = 'tabouret 2'
		),
		(
			SELECT
				id_categorie
			FROM
				categorie
			WHERE
				nom = 'Objets'
		)
	);