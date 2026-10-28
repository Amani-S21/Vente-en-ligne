<?php
$pageTitle = "Confirmation de Commande - NovaStore";
require_once __DIR__ . '/includes/header.php';

$orderId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="container" style="padding: 4rem 0; text-align: center;">
            <h2>Commande introuvable</h2>
            <a href="index.php" class="btn btn-primary">Retour à l\'accueil</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();
?>

<div class="container container-narrow" style="margin: 3rem auto 5rem;">
    <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 3rem; box-shadow: var(--shadow-lg); text-align: center;">
        <div style="width: 80px; height: 80px; background: #dcfce7; color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; margin: 0 auto 1.5rem; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);">
            <i class="bi bi-check-lg"></i>
        </div>

        <h1 style="font-size: 2.15rem; margin-bottom: 0.5rem;">Merci pour votre commande !</h1>
        <p style="color: var(--dark-muted); font-size: 1.05rem; margin-bottom: 2rem;">
            Votre achat a été validé avec succès. Un e-mail de confirmation a été envoyé à <strong><?= htmlspecialchars($order['shipping_email']) ?></strong>.
        </p>

        <!-- Cartouche Informations Commande -->
        <div style="background: var(--light); border: 1px solid var(--light-border); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2.5rem; text-align: left; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem;">
            <div>
                <span style="font-size: 0.8rem; color: var(--dark-muted); text-transform: uppercase; font-weight: 700;">N° de Commande</span>
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--dark);"><?= htmlspecialchars($order['order_number']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--dark-muted); text-transform: uppercase; font-weight: 700;">Code de Suivi Colis</span>
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary);"><?= htmlspecialchars($order['tracking_code']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--dark-muted); text-transform: uppercase; font-weight: 700;">Statut Paiement</span>
                <div>
                    <span class="badge-tag" style="background: var(--success); font-size: 0.75rem;">
                        <?= $order['payment_status'] === 'paid' ? 'Payée' : 'En attente' ?>
                    </span>
                </div>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--dark-muted); text-transform: uppercase; font-weight: 700;">Montant Réglé</span>
                <div style="font-size: 1.1rem; font-weight: 800; color: var(--dark); font-family: var(--font-heading);"><?= format_price($order['total_amount']) ?></div>
            </div>
        </div>

        <!-- Détail des articles commandés -->
        <div style="text-align: left; margin-bottom: 2.5rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1rem; border-bottom: 1px solid var(--light-border); padding-bottom: 0.5rem;">Articles commandés</h3>
            <?php foreach ($items as $item): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--light-border);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if ($item['image_url']): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width: 48px; height: 48px; border-radius: var(--radius-sm); object-fit: cover;">
                        <?php endif; ?>
                        <div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($item['product_name']) ?></div>
                            <small style="color: var(--dark-muted);">Quantité : <?= $item['quantity'] ?> &times; <?= format_price($item['price']) ?></small>
                        </div>
                    </div>
                    <strong><?= format_price($item['total']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Coordonnées de livraison -->
        <div style="text-align: left; background: #f1f5f9; padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 2.5rem; font-size: 0.9rem;">
            <div style="font-weight: 700; margin-bottom: 0.25rem;">Adresse de livraison :</div>
            <div><?= htmlspecialchars($order['shipping_name']) ?></div>
            <div><?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?></div>
            <div>Téléphone : <?= htmlspecialchars($order['shipping_phone']) ?></div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="track.php?code=<?= urlencode($order['tracking_code']) ?>" class="btn btn-primary btn-lg" id="trackOrderDirectBtn">
                <i class="bi bi-truck"></i> Suivre la livraison en direct
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-lg">
                <i class="bi bi-printer"></i> Imprimer le reçu
            </button>
            <a href="products.php" class="btn btn-dark btn-lg">
                Retour à la boutique
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
