<?php
require_once 'connexion.php';
require_once 'fonctions.php';

$page_title = 'Bon de sortie';
$alert = page_alert();
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editSortie = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT produit_id, quantite FROM sorties WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $sortie = $stmt->fetch();

            if (!$sortie) {
                throw new RuntimeException('Sortie introuvable.');
            }

            $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?')->execute([(int) $sortie['quantite'], (int) $sortie['produit_id']]);
            $pdo->prepare('DELETE FROM sorties WHERE id = ?')->execute([$id]);
            $pdo->commit();

            redirect_with_message('sorties.php', 'success', 'Sortie supprimee avec succes.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirect_with_message('sorties.php', 'danger', $e->getMessage());
        }
    }

    $id = (int) ($_POST['id'] ?? 0);
    $produitId = (int) ($_POST['produit_id'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 0);
    $dateSortie = $_POST['date_mouvement'] ?? '';

    if ($produitId <= 0 || $quantite <= 0 || $dateSortie === '' || !product_exists($pdo, $produitId)) {
        $alert = ['type' => 'danger', 'message' => 'Veuillez remplir tous les champs de la sortie.'];
    } elseif ($action === 'update') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT produit_id, quantite FROM sorties WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $ancienneSortie = $stmt->fetch();

            if (!$ancienneSortie) {
                throw new RuntimeException('Sortie introuvable.');
            }

            $ancienProduitId = (int) $ancienneSortie['produit_id'];
            $ancienneQuantite = (int) $ancienneSortie['quantite'];

            if ($ancienProduitId === $produitId) {
                $stockResultant = find_product_stock($pdo, $produitId) + $ancienneQuantite - $quantite;
                if ($stockResultant < 0) {
                    throw new RuntimeException('Stock insuffisant pour cette modification.');
                }

                $pdo->prepare('UPDATE produits SET stock = stock + ? - ? WHERE id = ?')
                    ->execute([$ancienneQuantite, $quantite, $produitId]);
            } else {
                $stockNouveau = find_product_stock($pdo, $produitId);
                if ($stockNouveau < $quantite) {
                    throw new RuntimeException('Stock insuffisant sur le nouveau produit.');
                }

                $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?')->execute([$ancienneQuantite, $ancienProduitId]);
                $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?')->execute([$quantite, $produitId]);
            }

            $pdo->prepare('UPDATE sorties SET produit_id = ?, quantite = ?, date_mouvement = ? WHERE id = ?')
                ->execute([$produitId, $quantite, $dateSortie, $id]);
            $pdo->commit();

            redirect_with_message('sorties.php', 'success', 'Sortie modifiee avec succes.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $alert = ['type' => 'danger', 'message' => $e->getMessage()];
        }
    } else {
        $stockActuel = find_product_stock($pdo, $produitId);

        if ($quantite > $stockActuel) {
            $alert = ['type' => 'danger', 'message' => 'Stock insuffisant pour cette sortie.'];
        } else {
            $insert = $pdo->prepare('INSERT INTO sorties (produit_id, quantite, date_mouvement) VALUES (?, ?, ?)');
            $insert->execute([$produitId, $quantite, $dateSortie]);
            $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?')->execute([$quantite, $produitId]);
            redirect_with_message('sorties.php', 'success', 'Sortie enregistree avec succes.');
        }
    }
}

$produits = $pdo->query('SELECT id, nom, stock FROM produits ORDER BY nom ASC')->fetchAll();
$sorties = $pdo->query(
    'SELECT s.id, s.produit_id, p.nom AS produit, s.quantite, s.date_mouvement
     FROM sorties s
     INNER JOIN produits p ON p.id = s.produit_id
     ORDER BY s.date_mouvement DESC, s.id DESC'
)->fetchAll();

if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT id, produit_id, quantite, date_mouvement FROM sorties WHERE id = ?');
    $stmt->execute([$editingId]);
    $editSortie = $stmt->fetch();
}

require_once 'includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <h2>Bon de sortie</h2>
        <p>Gerez les sorties avec verification du stock disponible et correction des anciens bons.</p>
    </div>

    <?php if ($alert['message'] !== ''): ?>
        <div class="alert <?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="<?php echo $editSortie ? 'update' : 'create'; ?>">
        <input type="hidden" name="id" value="<?php echo $editSortie['id'] ?? 0; ?>">
        <div>
            <label for="produit_id">Produit</label>
            <select id="produit_id" name="produit_id" required>
                <option value="">Selectionnez un produit</option>
                <?php foreach ($produits as $produit): ?>
                    <option value="<?php echo $produit['id']; ?>" <?php echo selected_value($editSortie['produit_id'] ?? '', $produit['id']); ?>>
                        <?php echo htmlspecialchars($produit['nom']); ?> (Stock: <?php echo (int) $produit['stock']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="quantite">Quantite</label>
            <input type="number" id="quantite" name="quantite" min="1" value="<?php echo htmlspecialchars($editSortie['quantite'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="date_mouvement">Date</label>
            <input type="date" id="date_mouvement" name="date_mouvement" value="<?php echo htmlspecialchars($editSortie['date_mouvement'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-action">
            <button type="submit" class="button"><?php echo $editSortie ? 'Mettre a jour' : 'Ajouter la sortie'; ?></button>
            <?php if ($editSortie): ?>
                <a class="button secondary" href="sorties.php">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <h3>Liste des sorties</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produit</th>
                    <th>Quantite</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sorties)): ?>
                    <tr>
                        <td colspan="5">Aucune sortie enregistree.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sorties as $sortie): ?>
                        <tr>
                            <td><?php echo $sortie['id']; ?></td>
                            <td><?php echo htmlspecialchars($sortie['produit']); ?></td>
                            <td><?php echo (int) $sortie['quantite']; ?></td>
                            <td><?php echo htmlspecialchars($sortie['date_mouvement']); ?></td>
                            <td>
                                <div class="actions">
                                    <a class="button secondary small" href="sorties.php?edit=<?php echo $sortie['id']; ?>">Modifier</a>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette sortie ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $sortie['id']; ?>">
                                        <button type="submit" class="button danger small">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require_once 'includes/footer.php'; ?>
