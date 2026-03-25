<?php
if (!isset($page_title)) {
    $page_title = 'Gestion des stocks';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div>
                <h1>Stock Pro</h1>
                <p>Gestion simple des mouvements commerciaux.</p>
            </div>
            <nav class="menu">
                <a href="index.php">Tableau de bord</a>
                <a href="produits.php">Produits</a>
                <a href="entrees.php">Bon d'entrees</a>
                <a href="sorties.php">Bon de sortie</a>
                <a href="commandes.php">Bon de commande</a>
                <a href="etat_sorties.php">Etats de sorties</a>
            </nav>
        </aside>
        <main class="content">
