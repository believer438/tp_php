<?php
require_once 'connexion.php';
require_once 'fonctions.php';

$page_title = 'Bon de commande';
$alert = page_alert();
$editingId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCommande = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $delete = $pdo->prepare('DELETE FROM commandes WHERE id = ?');
        $delete->execute([$id]);
        redirect_with_message('commandes.php', 'success', 'Commande supprimee avec succes.');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $produitId = (int) ($_POST['produit_id'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 0);
    $dateCommande = $_POST['date_mouvement'] ?? '';

    if ($produitId <= 0 || $quantite <= 0 || $dateCommande === '' || !product_exists($pdo, $produitId)) {
        $alert = ['type' => 'danger', 'message' => 'Veuillez remplir tous les champs de la commande.'];
    } elseif ($action === 'update') {
        $update = $pdo->prepare('UPDATE commandes SET produit_id = ?, quantite = ?, date_mouvement = ? WHERE id = ?');
        $update->execute([$produitId, $quantite, $dateCommande, $id]);
        redirect_with_message('commandes.php', 'success', 'Commande modifiee avec succes.');
    } else {
        $insert = $pdo->prepare('INSERT INTO commandes (produit_id, quantite, date_mouvement) VALUES (?, ?, ?)');
        $insert->execute([$produitId, $quantite, $dateCommande]);
        redirect_with_message('commandes.php', 'success', 'Commande enregistree avec succes.');
    }
}

$produits = $pdo->query('SELECT id, nom FROM produits ORDER BY nom ASC')->fetchAll();
$commandes = $pdo->query(
    'SELECT c.id, c.produit_id, p.nom AS produit, c.quantite, c.date_mouvement
     FROM commandes c
     INNER JOIN produits p ON p.id = c.produit_id
     ORDER BY c.date_mouvement DESC, c.id DESC'
)->fetchAll();

if ($editingId > 0) {
    $stmt = $pdo->prepare('SELECT id, produit_id, quantite, date_mouvement FROM commandes WHERE id = ?');
    $stmt->execute([$editingId]);
    $editCommande = $stmt->fetch();
}

require_once 'includes/header.php';
?>

<section class="panel">
    <div class="panel-header">
        <h2>Bon de commande caisse</h2>
        
    </div>

    <?php if ($alert['message'] !== ''): ?>
        <div class="alert <?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?>">
            <?php echo htmlspecialchars($alert['message']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="<?php echo $editCommande ? 'update' : 'create'; ?>">
        <input type="hidden" name="id" value="<?php echo $editCommande['id'] ?? 0; ?>">
        <div>
            <label for="produit_id">Produit</label>
            <select id="produit_id" name="produit_id" required>
                <option value="">Selectionnez un produit</option>
                <?php foreach ($produits as $produit): ?>
                    <option value="<?php echo $produit['id']; ?>" <?php echo selected_value($editCommande['produit_id'] ?? '', $produit['id']); ?>>
                        <?php echo htmlspecialchars($produit['nom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="quantite">Quantite</label>
            <input type="number" id="quantite" name="quantite" min="1" value="<?php echo htmlspecialchars($editCommande['quantite'] ?? ''); ?>" required>
        </div>
        <div>
            <label for="date_mouvement">Date</label>
            <input type="date" id="date_mouvement" name="date_mouvement" value="<?php echo htmlspecialchars($editCommande['date_mouvement'] ?? date('Y-m-d')); ?>" required>
        </div>
        <div class="form-action">
            <button type="submit" class="button"><?php echo $editCommande ? 'Mettre a jour' : 'Ajouter la commande'; ?></button>
            <?php if ($editCommande): ?>
                <a class="button secondary" href="commandes.php">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <h3>Liste des commandes</h3>
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
                <?php if (empty($commandes)): ?>
                    <tr>
                        <td colspan="5">Aucune commande enregistree.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td><?php echo $commande['id']; ?></td>
                            <td><?php echo htmlspecialchars($commande['produit']); ?></td>
                            <td><?php echo (int) $commande['quantite']; ?></td>
                            <td><?php echo htmlspecialchars($commande['date_mouvement']); ?></td>
                            <td>
                                <div class="actions">
                                    <a class="button secondary small" href="commandes.php?edit=<?php echo $commande['id']; ?>">Modifier</a>
                                    <form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette commande ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $commande['id']; ?>">
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
