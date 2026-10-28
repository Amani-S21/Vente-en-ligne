<?php
$pageTitle = "Administration - Gestion des Commandes - NovaStore";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('../login.php');

$pdo = getDBConnection();

// Traitement changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? 'pending');
        $newPaymentStatus = sanitize($_POST['payment_status'] ?? 'paid');

        if ($orderId > 0) {
            $up = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $up->execute([$newStatus, $newPaymentStatus, $orderId]);
            set_flash_message('success', 'Statut de la commande mis à jour avec succès.');
        }
        header('Location: orders.php');
        exit;
    }
}

// Filtre par statut
$statusFilter = sanitize($_GET['status'] ?? 'all');
$sql = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($statusFilter !== 'all' && $statusFilter !== '') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Affichage d'une commande spécifique
$viewId = (int)($_GET['view'] ?? 0);
$viewOrder = null;
$viewItems = [];

if ($viewId > 0) {
    $vStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $vStmt->execute([$viewId]);
    $viewOrder = $vStmt->fetch();

    if ($viewOrder) {
        $itStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itStmt->execute([$viewId]);
        $viewItems = $itStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav { background: #0f172a; color: white; padding: 1rem 0; }
        .admin-nav .container { display: flex; justify-content: space-between; align-items: center; }
        .admin-menu { display: flex; gap: 1.5rem; list-style: none; }
        .admin-menu a { color: #94a3b8; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; }
        .admin-menu a.active, .admin-menu a:hover { color: white; }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <!-- Navigation Admin -->
    <header class="admin-nav">
        <div class="container">
            <a href="../index.php" class="brand-logo" style="color: white; font-size: 1.25rem;">
                <div class="logo-icon" style="width: 32px; height: 32px; font-size: 1rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <span>Nova<span>Admin</span></span>
            </a>

            <ul class="admin-menu">
                <li><a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="bi bi-box-seam"></i> Produits</a></li>
                <li><a href="orders.php" class="active"><i class="bi bi-receipt"></i> Commandes</a></li>
                <li><a href="../index.php" target="_blank"><i class="bi bi-shop"></i> Voir la boutique</a></li>
                <li><a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
            </ul>
        </div>
    </header>

    <div class="container" style="margin: 2.5rem auto 5rem;">
        <?php foreach (get_flash_messages() as $f): ?>
            <div class="alert alert-<?= $f['type'] ?>"><?= htmlspecialchars($f['message']) ?></div>
        <?php endforeach; ?>

        <?php if ($viewOrder): ?>
            <!-- Vue détaillée d'une commande -->
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2.5rem; max-width: 900px; margin: 0 auto; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--light-border); padding-bottom: 1rem;">
                    <div>
                        <h2 style="font-size: 1.5rem; margin-bottom: 0.2rem;">Commande <?= htmlspecialchars($viewOrder['order_number']) ?></h2>
                        <span style="font-size: 0.85rem; color: var(--dark-muted);">Suivi : <strong><?= htmlspecialchars($viewOrder['tracking_code']) ?></strong></span>
                    </div>
                    <a href="orders.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem; margin-bottom: 2rem;">
                    <!-- Adresse & Coordonnées -->
                    <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius-md);">
                        <h4 style="font-size: 1rem; margin-bottom: 0.75rem;">Destinataire & Expédition</h4>
                        <p style="margin-bottom: 0.3rem;"><strong>Nom :</strong> <?= htmlspecialchars($viewOrder['shipping_name']) ?></p>
                        <p style="margin-bottom: 0.3rem;"><strong>Email :</strong> <?= htmlspecialchars($viewOrder['shipping_email']) ?></p>
                        <p style="margin-bottom: 0.3rem;"><strong>Téléphone :</strong> <?= htmlspecialchars($viewOrder['shipping_phone']) ?></p>
                        <p style="margin-bottom: 0.3rem;"><strong>Adresse :</strong> <?= htmlspecialchars($viewOrder['shipping_address']) ?>, <?= htmlspecialchars($viewOrder['shipping_city']) ?></p>
                        <p style="margin-bottom: 0;"><strong>Règlement :</strong> <?= strtoupper($viewOrder['payment_method']) ?> (<?= $viewOrder['payment_status'] ?>)</p>
                    </div>

                    <!-- Modification de statut en direct -->
                    <div style="background: var(--light); padding: 1.5rem; border-radius: var(--radius-md);">
                        <h4 style="font-size: 1rem; margin-bottom: 1rem;">Mise à jour de l'état</h4>
                        <form action="orders.php" method="POST">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="update_status" value="1">
                            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">

                            <div class="form-group">
                                <label class="form-label">Statut Logistique :</label>
                                <select name="status" class="form-control">
                                    <option value="pending" <?= $viewOrder['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                    <option value="paid" <?= $viewOrder['status'] === 'paid' ? 'selected' : '' ?>>Validée / Payée</option>
                                    <option value="preparing" <?= $viewOrder['status'] === 'preparing' ? 'selected' : '' ?>>En préparation</option>
                                    <option value="shipped" <?= $viewOrder['status'] === 'shipped' ? 'selected' : '' ?>>Expédiée</option>
                                    <option value="delivered" <?= $viewOrder['status'] === 'delivered' ? 'selected' : '' ?>>Livrée au client</option>
                                    <option value="cancelled" <?= $viewOrder['status'] === 'cancelled' ? 'selected' : '' ?>>Annulée</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Statut Paiement :</label>
                                <select name="payment_status" class="form-control">
                                    <option value="paid" <?= $viewOrder['payment_status'] === 'paid' ? 'selected' : '' ?>>Payé</option>
                                    <option value="pending" <?= $viewOrder['payment_status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-sm">
                                <i class="bi bi-check2-circle"></i> Mettre à jour le suivi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Articles de la commande -->
                <h4 style="font-size: 1.1rem; margin-bottom: 1rem;">Articles commandés</h4>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix unitaire</th>
                            <th>Quantité</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewItems as $it): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if ($it['image_url']): ?>
                                            <img src="<?= htmlspecialchars($it['image_url']) ?>" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($it['product_name']) ?></strong>
                                    </div>
                                </td>
                                <td><?= format_price($it['price']) ?></td>
                                <td>&times; <?= $it['quantity'] ?></td>
                                <td style="font-weight: 700;"><?= format_price($it['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 1.5rem; font-size: 1.35rem; font-weight: 800;">
                    Total Commande : <span style="color: var(--primary);"><?= format_price($viewOrder['total_amount']) ?></span>
                </div>
            </div>

        <?php else: ?>
            <!-- Liste générale des commandes -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 2rem;">Gestion des Commandes (<?= count($orders) ?>)</h1>
                    <p style="color: var(--dark-muted); font-size: 0.95rem;">Suivi logistique, validation des paiements et expéditions</p>
                </div>

                <!-- Filtre statut -->
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="orders.php?status=all" class="btn btn-<?= $statusFilter === 'all' ? 'primary' : 'secondary' ?> btn-sm">Toutes</a>
                    <a href="orders.php?status=paid" class="btn btn-<?= $statusFilter === 'paid' ? 'primary' : 'secondary' ?> btn-sm">Payées</a>
                    <a href="orders.php?status=preparing" class="btn btn-<?= $statusFilter === 'preparing' ? 'primary' : 'secondary' ?> btn-sm">En Préparation</a>
                    <a href="orders.php?status=shipped" class="btn btn-<?= $statusFilter === 'shipped' ? 'primary' : 'secondary' ?> btn-sm">Expédiées</a>
                    <a href="orders.php?status=delivered" class="btn btn-<?= $statusFilter === 'delivered' ? 'primary' : 'secondary' ?> btn-sm">Livrées</a>
                </div>
            </div>

            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="overflow-x: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Ville</th>
                                <th>Paiement</th>
                                <th>Statut Expédition</th>
                                <th>Montant</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $ord): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($ord['order_number']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--primary);">
                                            <?= htmlspecialchars($ord['tracking_code']) ?>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--dark-muted);">
                                        <?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div><strong><?= htmlspecialchars($ord['shipping_name']) ?></strong></div>
                                        <div style="font-size: 0.75rem; color: var(--dark-muted);"><?= htmlspecialchars($ord['shipping_phone']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($ord['shipping_city']) ?></td>
                                    <td>
                                        <span class="badge-tag" style="background: <?= $ord['payment_status'] === 'paid' ? '#dcfce7; color: #166534;' : '#fef3c7; color: #92400e;' ?> font-size: 0.7rem;">
                                            <?= strtoupper($ord['payment_method']) ?> : <?= strtoupper($ord['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-tag" style="background: <?= $ord['status'] === 'delivered' ? 'var(--success)' : ($ord['status'] === 'shipped' ? 'var(--info)' : 'var(--warning)') ?>; font-size: 0.75rem;">
                                            <?= strtoupper($ord['status']) ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 800;"><?= format_price($ord['total_amount']) ?></td>
                                    <td>
                                        <a href="orders.php?view=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.7rem; font-size: 0.8rem;">
                                            <i class="bi bi-eye"></i> Gérer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
