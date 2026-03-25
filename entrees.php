<?php
require_once 'connexion.php';
require_once 'fonctions.php';

$page_title = 'Bon d\'entrees';
$alert = page_alert();
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editEntree = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT produit_id, quantite FROM entrees WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $entree = $stmt->fetch();

            if (!$entree) {
                throw new RuntimeException('Entree introuvable.');
            }

            $stockActuel = find_product_stock($pdo, (int) $entree['produit_id']);
            if ($stockActuel < (int) $entree['quantite']) {
                throw new RuntimeException('Suppression impossible car cette entree a deja ete consommee par des sorties.');
            }

            $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?')->execute([(int) $entree['quantite'], (int) $entree['produit_id']]);
            $pdo->prepare('DELETE FROM entrees WHERE id = ?')->execute([$id]);
            $pdo->commit();

            redirect_with_message('entrees.php', 'success', 'Entree supprimee avec succes.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirect_with_message('entrees.php', 'danger', $e->getMessage());
        }
    }

    $id = (int) ($_POST['id'] ?? 0);
    $produitId = (int) ($_POST['produit_id'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 0);
    $dateEntree = $_POST['date_mouvement'] ?? '';

    if ($produitId <= 0 || $quantite <= 0 || $dateEntree === '' || !product_exists($pdo, $produitId)) {
        $alert = ['type' => 'danger', 'message' => 'Veuillez remplir tous les champs de l\'entree.'];
    } elseif ($action === 'update') {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT produit_id, quantite FROM entrees WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $ancienneEntree = $stmt->fetch();

            if (!$ancienneEntree) {
                throw new RuntimeException('Entree introuvable.');
            }

            $ancienProduitId = (int) $ancienneEntree['produit_id'];
            $ancienneQuantite = (int) $ancienneEntree['quantite'];

            if ($ancienProduitId === $produitId) {
                $stockResultant = find_product_stock($pdo, $produitId) - $ancienneQuantite + $quantite;
                if ($stockResultant < 0) {
                    throw new RuntimeException('Modification impossible: le stock deviendrait negatif.');
                }

                $pdo->prepare('UPDATE produits SET stock = stock - ? + ? WHERE id = ?')
                    ->execute([$ancienneQuantite, $quantite, $produitId]);
            } else {
                $stockAncien = find_product_stock($pdo, $ancienProduitId);
                if ($stockAncien < $ancienneQuantite) {
                    throw new RuntimeException('Modification impossible car l\'ancienne entree a deja ete consommee.');
                }

                $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ?')->execute([$ancienneQuantite, $ancienProduitId]);
                $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?')->execute([$quantite, $produitId]);
            }

            $pdo->prepare('UPDATE entrees SET produit_id = ?, quantite = ?, date_mouvement = ? WHERE id = ?')
                ->execute([$produitId, $quantite, $dateEntree, $id]);
            $pdo->commit();

            redirect_with_message('entrees.php', 'success', 'Entree modifiee avec succes.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $alert = ['type' => 'danger', 'message' => $e->getMessage()];
        }
    } else {
        $insert = $pdo->prepare('INSERT INTO entrees (produit_id, quantite, date_mouvement) VALUES (?, ?, ?)');
        $insert->execute([$produitId, $quantite, $dateEntree]);
        $pdo->prepare('UPDATE produits SET stock = stock + ? WHERE id = ?')->execute([$quantite, $produitId]);
        redirect_with_message('entrees.php', 'success', 'Entree enregistree avec succes.');
    }
}

$produits = $pdo->query('SELECT id, nom FROM produits ORDER BY nom ASC')->fetchAll();
$entrees = $pdo->query(
    'SELECT e.id, e.produit_id, p.nom AS produit, e.quantite, e.date_mouvement
     FROM entrees e
     INNER JOIN produits p ON p.id = e.produit_id
     ORDER BY e.date_mouvement DESC, e.id DESC'
)->fetchAll();

if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT id, produit_id, quantite, date_mouvement FROM entrees WHERE id = ?');
    $stmt->execute([$editingId]);
    $editEntree = $stmt->fetch();
}

require_once 'includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <h2>Bon d'entrees</h2>
        <p>Ajoutez, corrigez ou supprimez les entrees de marchandises avec mise a jour du stock.</p>
    </div>

    <?php if ($alert['message'] !== ''): ?>
        <div class="alert <?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="<?php echo $editEntree ? 'update' : 'create'; ?>">
        <input type="hidden" name="id" value="<?php echo $editEntree['id'] ?? 0; ?>">
        <div>
            <label for="produit_id">Produit</label>
            <select id="produit_id" name="produit_id" required>
                <option value="">Selectionnez un produit</option>
                <?php foreach ($produits as $produit): ?>
                    <option value="<?php echo $produit['id']; ?>" <?php echo selected_value($editEntree['produit_id'] ?? '', $produit['id']); ?>>
                        <?php echo htmlspecialchars($produit['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="quantite">Quantite</label>
            <input type="number" id="quantite" name="quantite" min="1" value="<?php echo htmlspecialchars($editEntree['quantite'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="date_mouvement">Date</label>
            <input type="date" id="date_mouvement" name="date_mouvement" value="<?php echo htmlspecialchars($editEntree['date_mouvement'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-action">
            <button type="submit" class="button"><?php echo $editEntree ? 'Mettre a jour' : 'Ajouter l\'entree'; ?></button>
            <?php if ($editEntree): ?>
                <a class="button secondary" href="entrees.php">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <h3>Liste des entrees</h3>
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
                <?php if (empty($entrees)): ?>
                    <tr>
                        <td colspan="5">Aucune entree enregistree.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entrees as $entree): ?>
                        <tr>
                            <td><?php echo $entree['id']; ?></td>
                            <td><?php echo htmlspecialchars($entree['produit']); ?></td>
                            <td><?php echo (int) $entree['quantite']; ?></td>
                            <td><?php echo htmlspecialchars($entree['date_mouvement']); ?></td>
                            <td>
                                <div class="actions">
                                    <a class="button secondary small" href="entrees.php?edit=<?php echo $entree['id']; ?>">Modifier</a>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette entree ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $entree['id']; ?>">
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
