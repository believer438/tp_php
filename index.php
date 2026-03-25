<?php
require_once 'connexion.php';

$page_title = 'Tableau de bord';

$totalProduits = (int) $pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();
$totalEntrees = (int) $pdo->query('SELECT COUNT(*) FROM entrees')->fetchColumn();
$totalSorties = (int) $pdo->query('SELECT COUNT(*) FROM sorties')->fetchColumn();
$totalCommandes = (int) $pdo->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
$quantiteEntrees = (int) $pdo->query('SELECT COALESCE(SUM(quantite), 0) FROM entrees')->fetchColumn();
$quantiteSorties = (int) $pdo->query('SELECT COALESCE(SUM(quantite), 0) FROM sorties')->fetchColumn();
$stockTotal = (int) $pdo->query('SELECT COALESCE(SUM(stock), 0) FROM produits')->fetchColumn();
$stockFaible = (int) $pdo->query('SELECT COUNT(*) FROM produits WHERE stock BETWEEN 1 AND 10')->fetchColumn();

$stocks = $pdo->query('SELECT nom, stock FROM produits ORDER BY nom ASC')->fetchAll();
$mouvementsRecents = $pdo->query(
    "(SELECT 'Entree' AS type_bon, p.nom AS produit, e.quantite, e.date_mouvement
      FROM entrees e
      INNER JOIN produits p ON p.id = e.produit_id)
     UNION ALL
     (SELECT 'Sortie' AS type_bon, p.nom AS produit, s.quantite, s.date_mouvement
      FROM sorties s
      INNER JOIN produits p ON p.id = s.produit_id)
     UNION ALL
     (SELECT 'Commande' AS type_bon, p.nom AS produit, c.quantite, c.date_mouvement
      FROM commandes c
      INNER JOIN produits p ON p.id = c.produit_id)
     ORDER BY date_mouvement DESC, quantite DESC
     LIMIT 8"
)->fetchAll();

require_once 'includes/header.php';
?>
<section class="hero">
    <div>
        <h2>Tableau de bord</h2>
        <p>Suivez les mouvements de stock, les bons saisis et les produits a surveiller depuis une interface plus complete.</p>
    </div>
</section>

<section class="cards">
    <article class="card">
        <span>Produits</span>
        <strong><?php echo $totalProduits; ?></strong>
    </article>
    <article class="card">
        <span>Entrees</span>
        <strong><?php echo $totalEntrees; ?></strong>
    </article>
    <article class="card">
        <span>Sorties</span>
        <strong><?php echo $totalSorties; ?></strong>
    </article>
    <article class="card">
        <span>Commandes</span>
        <strong><?php echo $totalCommandes; ?></strong>
    </article>
    <article class="card accent">
        <span>Stock total</span>
        <strong><?php echo $stockTotal; ?></strong>
    </article>
    <article class="card accent">
        <span>Stock faible</span>
        <strong><?php echo $stockFaible; ?></strong>
    </article>
    <article class="card accent">
        <span>Quantites entrees</span>
        <strong><?php echo $quantiteEntrees; ?></strong>
    </article>
    <article class="card accent">
        <span>Quantites sorties</span>
        <strong><?php echo $quantiteSorties; ?></strong>
    </article>
</section>

<section class="panel">
    <div class="panel-header">
        <h3>Etat rapide du stock</h3>
        <a class="button secondary" href="etat_sorties.php">Voir le resume des sorties</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Stock actuel</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stocks)): ?>
                    <tr>
                        <td colspan="2">Aucun produit enregistre pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stocks as $stock): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stock['nom']); ?></td>
                            <td>
                                <span class="stock-badge <?php echo (int) $stock['stock'] <= 10 ? 'low' : 'ok'; ?>">
                                    <?php echo (int) $stock['stock']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <h3>Mouvements recents</h3>
        <p>Les derniers bons enregistres dans l'application.</p>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Produit</th>
                    <th>Quantite</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mouvementsRecents)): ?>
                    <tr>
                        <td colspan="4">Aucun mouvement disponible.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mouvementsRecents as $mouvement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mouvement['type_bon']); ?></td>
                            <td><?php echo htmlspecialchars($mouvement['produit']); ?></td>
                            <td><?php echo (int) $mouvement['quantite']; ?></td>
                            <td><?php echo htmlspecialchars($mouvement['date_mouvement']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
