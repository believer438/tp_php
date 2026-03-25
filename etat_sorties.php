<?php
require_once 'connexion.php';

$page_title = 'Etats de sorties';

$resumeSorties = $pdo->query(
    'SELECT p.nom AS produit, p.stock, COALESCE(SUM(s.quantite), 0) AS total_sorties
     FROM produits p
     LEFT JOIN sorties s ON s.produit_id = p.id
     GROUP BY p.id, p.nom, p.stock
     ORDER BY total_sorties DESC, p.nom ASC'
)->fetchAll();

require_once 'includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <h2>Etats de sorties</h2>
        <p>Resume des quantites sorties par produit avec le stock encore disponible.</p>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Total sorties</th>
                    <th>Stock actuel</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($resumeSorties)): ?>
                    <tr>
                        <td colspan="3">Aucune donnee disponible.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resumeSorties as $ligne): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ligne['produit']); ?></td>
                            <td><?php echo (int) $ligne['total_sorties']; ?></td>
                            <td><?php echo (int) $ligne['stock']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
