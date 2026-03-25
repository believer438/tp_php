# Application de gestion commerciale en PHP

Cette application permet de gerer :

- les produits
- les entrees de stock
- les sorties de stock
- les commandes
- le resume des sorties par produit
- la modification et la suppression des enregistrements

## Installation locale avec XAMPP

1. Copier le projet dans `C:\xampp\htdocs\tp_php`
2. Demarrer `Apache` et `MySQL` depuis XAMPP
3. Ouvrir `phpMyAdmin`
4. Importer le fichier `database.sql`
5. Verifier les parametres de connexion dans `connexion.php`
6. Ouvrir dans le navigateur : `http://localhost/tp_php/`

## Parametres MySQL par defaut

- Base de donnees : `gestion_commerciale`
- Utilisateur : `root`
- Mot de passe : vide

## Bonus stock

Le stock est mis a jour automatiquement :

- lors d'une entree : ajout au stock
- lors d'une sortie : retrait du stock avec controle de disponibilite
- lors d'une modification ou suppression d'une entree/sortie : recalcul automatique et securise
