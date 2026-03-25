<?php
require_once 'connexion.php';
require_once 'fonctions.php';

$page_title = 'Produits';
$alert = page_alert();
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editProduit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        $verif = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM entrees WHERE produit_id = ?) AS total_entrees,
                (SELECT COUNT(*) FROM sorties WHERE produit_id = ?) AS total_sorties,
                (SELECT COUNT(*) FROM commandes WHERE produit_id = ?) AS total_commandes'
        );
        $verif->execute([$id, $id, $id]);
        $totaux = $verif->fetch();

        if (!$totaux) {
            redirect_with_message('produits.php', 'danger', 'Produit introuvable.');
        }

        if ((int) $totaux['total_entrees'] > 0 || (int) $totaux['total_sorties'] > 0 || (int) $totaux['total_commandes'] > 0) {
            redirect_with_message('produits.php', 'danger', 'Impossible de supprimer un produit deja utilise dans les bons.');
        }

        $delete = $pdo->prepare('DELETE FROM produits WHERE id = ?');
        $delete->execute([$id]);
        redirect_with_message('produits.php', 'success', 'Produit supprime avec succes.');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');

    if ($nom === '') {
        $alert = ['type' => 'danger', 'message' => 'Le nom du produit est obligatoire.'];
    } else {
        $verif = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE nom = ? AND id != ?');
        $verif->execute([$nom, $id]);

        if ((int) $verif->fetchColumn() > 0) {
            $alert = ['type' => 'danger', 'message' => 'Ce produit existe deja.'];
        } elseif ($action === 'update') {
            $update = $pdo->prepare('UPDATE produits SET nom = ? WHERE id = ?');
            $update->execute([$nom, $id]);
            redirect_with_message('produits.php', 'success', 'Produit modifie avec succes.');
        } else {
            $insert = $pdo->prepare('INSERT INTO produits (nom) VALUES (?)');
            $insert->execute([$nom]);
            redirect_with_message('produits.php', 'success', 'Produit ajoute avec succes.');
        }
    }
}

$produits = $pdo->query('SELECT id, nom, stock FROM produits ORDER BY nom ASC')->fetchAll();

if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT id, nom FROM produits WHERE id = ?');
    $stmt->execute([$editingId]);
    $editProduit = $stmt->fetch();
}

require_once 'includes/header.php';
?>
<section class="panel">
    <div class="panel-header">
        <h2>Produits</h2>
        <p>Ajoutez, modifiez et organisez les produits de reference utilises dans les bons.</p>
    </div>

    <?php if ($alert['message'] !== ''): ?>
        <div class="alert <?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="<?php echo $editProduit ? 'update' : 'create'; ?>">
        <input type="hidden" name="id" value="<?php echo $editProduit['id'] ?? 0; ?>">
        <div>
            <label for="nom">Nom du produit</label>
            <input type="text" id="nom" name="nom" placeholder="Ex: Sucre 25 kg" value="<?php echo htmlspecialchars($editProduit['nom'] ?? ''); ?>" required>
        </div>
        <div class="form-action">
            <button type="submit" class="button"><?php echo $editProduit ? 'Mettre a jour' : 'Ajouter'; ?></button>
            <?php if ($editProduit): ?>
                <a class="button secondary" href="produits.php">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <h3>Liste des produits</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produit</th>
                    <th>Stock actuel</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produits)): ?>
                    <tr>
                        <td colspan="4">Aucun produit disponible.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produits as $produit): ?>
                        <tr>
                            <td><?php echo $produit['id']; ?></td>
                            <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                            <td><?php echo (int) $produit['stock']; ?></td>
                            <td>
                                <div class="actions">
                                    <a class="button secondary small" href="produits.php?edit=<?php echo $produit['id']; ?>">Modifier</a>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Supprimer ce produit ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $produit['id']; ?>">
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
