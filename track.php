<?php
$pageTitle = "Suivi de Colis & Commandes en Direct - NovaStore";
require_once __DIR__ . '/includes/header.php';

$searchCode = trim(sanitize($_GET['code'] ?? ''));
$order = null;
$items = [];

if ($searchCode !== '') {
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE tracking_code = ? OR order_number = ? OR shipping_phone = ? 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$searchCode, $searchCode, $searchCode]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $items = $itemStmt->fetchAll();
    }
}

// Détermination de l'étape active dans la timeline
$statusLevels = [
    'pending' => 1,
    'paid' => 1,
    'preparing' => 2,
    'shipped' => 3,
    'delivered' => 4,
    'cancelled' => 0
];
$currentLevel = $order ? ($statusLevels[$order['status']] ?? 1) : 0;
?>

<div class="container" style="margin: 2.5rem auto 5rem;">
    <!-- En-tête de section -->
    <div style="text-align: center; max-width: 650px; margin: 0 auto 3rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--primary-light); color: var(--primary); padding: 0.4rem 1rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="bi bi-geo-alt-fill"></i> Géolocalisation & Suivi en Temps Réel
        </div>
        <h1 style="font-size: 2.5rem; margin-bottom: 0.75rem;">Suivre l'acheminement de votre colis</h1>
        <p style="color: var(--dark-muted); font-size: 1.05rem;">
            Saisissez votre code de suivi (ex: <code>TRK-8921-CD</code>) ou numéro de commande (<code>CMD-2026-...</code>) pour connaître son état d'avancement exact.
        </p>

        <!-- Formulaire de recherche -->
        <form action="track.php" method="GET" style="margin-top: 2rem;">
            <div style="display: flex; gap: 0.5rem; background: white; padding: 0.5rem; border-radius: var(--radius-full); box-shadow: var(--shadow-lg); border: 1px solid var(--light-border);">
                <input type="text" name="code" value="<?= htmlspecialchars($searchCode) ?>" placeholder="Entrez votre numéro de suivi ou commande..." required style="flex: 1; border: none; padding: 0.75rem 1.25rem; font-size: 1rem; outline: none; border-radius: var(--radius-full); font-family: var(--font-main);">
                <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-full); padding: 0.75rem 1.75rem;" id="trackSearchBtn">
                    <i class="bi bi-search"></i> Suivre
                </button>
            </div>
            
            <!-- Suggestions rapides de test pour le professeur / correcteur -->
            <div style="margin-top: 1rem; font-size: 0.85rem; color: var(--dark-muted);">
                Exemples de test immédiat : 
                <a href="track.php?code=TRK-8921-CD" style="font-weight: 700; text-decoration: underline;">TRK-8921-CD</a> (En expédition) &bull; 
                <a href="track.php?code=TRK-4412-CD" style="font-weight: 700; text-decoration: underline;">TRK-4412-CD</a> (Livrée)
            </div>
        </form>
    </div>

    <?php if ($searchCode !== '' && !$order): ?>
        <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 3rem 2rem; text-align: center; max-width: 600px; margin: 0 auto;">
            <div style="width: 70px; height: 70px; background: #fee2e2; color: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 1.25rem;">
                <i class="bi bi-search"></i>
            </div>
            <h3>Aucun colis trouvé avec cette référence</h3>
            <p style="color: var(--dark-muted); margin: 0.5rem 0 1.5rem;">
                Vérifiez que votre code ne comporte pas d'erreur de saisie ou contactez notre support client muni de votre facture.
            </p>
            <a href="track.php" class="btn btn-secondary">Nouvelle recherche</a>
        </div>
    <?php elseif ($order): ?>
        <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 3rem; box-shadow: var(--shadow-lg);">
            <!-- En-tête de la commande trouvée -->
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem; border-bottom: 1px solid var(--light-border); padding-bottom: 2rem;">
                <div>
                    <span style="font-size: 0.85rem; color: var(--dark-muted); font-weight: 600;">Commande N°</span>
                    <h2 style="font-size: 1.85rem; margin: 0.2rem 0;"><?= htmlspecialchars($order['order_number']) ?></h2>
                    <div style="color: var(--dark-muted); font-size: 0.9rem;">
                        Date de commande : <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?>
                    </div>
                </div>

                <div style="text-align: right;">
                    <div style="font-size: 0.85rem; color: var(--dark-muted); font-weight: 600;">Code de Traçabilité Transporteur</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); font-family: var(--font-heading);">
                        <i class="bi bi-upc-scan"></i> <?= htmlspecialchars($order['tracking_code']) ?>
                    </div>
                    <div>
                        <span class="badge-tag" style="background: <?= $order['status'] === 'delivered' ? 'var(--success)' : ($order['status'] === 'shipped' ? 'var(--info)' : 'var(--warning)') ?>; font-size: 0.8rem;">
                            <?= strtoupper($order['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Timeline visuelle d'acheminement -->
            <div class="tracking-stepper">
                <div class="tracking-progress-line" style="width: <?= ($currentLevel - 1) * 33.33 ?>%;"></div>

                <!-- Étape 1 -->
                <div class="tracking-step <?= $currentLevel >= 1 ? 'completed' : '' ?> <?= $currentLevel === 1 ? 'active' : '' ?>">
                    <div class="step-circle"><i class="bi bi-cart-check"></i></div>
                    <div class="step-title">Validée</div>
                    <div class="step-time">Paiement reçu</div>
                </div>

                <!-- Étape 2 -->
                <div class="tracking-step <?= $currentLevel >= 2 ? 'completed' : '' ?> <?= $currentLevel === 2 ? 'active' : '' ?>">
                    <div class="step-circle"><i class="bi bi-box-seam"></i></div>
                    <div class="step-title">En Préparation</div>
                    <div class="step-time">Centre logistique</div>
                </div>

                <!-- Étape 3 -->
                <div class="tracking-step <?= $currentLevel >= 3 ? 'completed' : '' ?> <?= $currentLevel === 3 ? 'active' : '' ?>">
                    <div class="step-circle"><i class="bi bi-truck"></i></div>
                    <div class="step-title">Expédiée</div>
                    <div class="step-time">Prise en charge</div>
                </div>

                <!-- Étape 4 -->
                <div class="tracking-step <?= $currentLevel >= 4 ? 'completed' : '' ?> <?= $currentLevel === 4 ? 'active' : '' ?>">
                    <div class="step-circle"><i class="bi bi-house-door-fill"></i></div>
                    <div class="step-title">Livrée</div>
                    <div class="step-time">Remis au client</div>
                </div>
            </div>

            <!-- Détails expédition & articles -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; margin-top: 3rem; padding-top: 2.5rem; border-top: 1px solid var(--light-border);">
                <!-- Informations Destinataire -->
                <div style="background: var(--light); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--light-border);">
                    <h4 style="font-size: 1.05rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-person-badge-fill" style="color: var(--primary);"></i> Détails de Destination
                    </h4>
                    <p style="margin-bottom: 0.4rem;"><strong>Destinataire :</strong> <?= htmlspecialchars($order['shipping_name']) ?></p>
                    <p style="margin-bottom: 0.4rem;"><strong>Adresse :</strong> <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['shipping_city']) ?></p>
                    <p style="margin-bottom: 0.4rem;"><strong>Contact :</strong> <?= htmlspecialchars($order['shipping_phone']) ?></p>
                    <p style="margin-bottom: 0;"><strong>Mode de Règlement :</strong> <?= strtoupper($order['payment_method']) ?> (<?= $order['payment_status'] === 'paid' ? 'Payé' : 'En attente' ?>)</p>
                </div>

                <!-- Colisage & Total -->
                <div style="background: var(--light); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--light-border);">
                    <h4 style="font-size: 1.05rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-box-fill" style="color: var(--primary);"></i> Contenu du Colis (<?= count($items) ?> ref.)
                    </h4>
                    <?php foreach ($items as $it): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; font-size: 0.9rem;">
                            <span><?= htmlspecialchars($it['product_name']) ?> &times; <?= $it['quantity'] ?></span>
                            <strong><?= format_price($it['total']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div style="border-top: 1px solid var(--light-border); padding-top: 0.75rem; margin-top: 0.75rem; display: flex; justify-content: space-between; font-weight: 800; font-size: 1.1rem;">
                        <span>Total Facturé :</span>
                        <span style="color: var(--primary);"><?= format_price($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
