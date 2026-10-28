<?php
$pageTitle = "Mon Panier d'Achat - NovaStore";
require_once __DIR__ . '/includes/header.php';

$cart = get_cart();
$totals = get_cart_totals();
?>

<div class="container">
    <div style="padding: 1.5rem 0 0.5rem; font-size: 0.85rem; color: var(--dark-muted);">
        <a href="index.php">Accueil</a> &bull; <span>Panier d'achat</span>
    </div>

    <h1 style="font-size: 2.25rem; margin-bottom: 2rem;">Votre Panier d'Achat (<?= $totals['item_count'] ?>)</h1>

    <?php if (empty($cart)): ?>
        <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 4.5rem 2rem; text-align: center; margin-bottom: 4rem;">
            <div style="width: 80px; height: 80px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem;">
                <i class="bi bi-cart-x"></i>
            </div>
            <h2>Votre panier est actuellement vide</h2>
            <p style="color: var(--dark-muted); max-width: 450px; margin: 0.5rem auto 1.75rem;">
                Vous n'avez pas encore sélectionné d'articles. Explorez notre catalogue pour trouver les meilleurs produits tech et lifestyle !
            </p>
            <a href="products.php" class="btn btn-primary btn-lg" id="emptyCartExploreBtn">
                <i class="bi bi-shop"></i> Découvrir les articles
            </a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Table des articles -->
            <div class="cart-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.35rem;">Articles sélectionnés</h2>
                    <form action="cart-action.php" method="POST" onsubmit="return confirm('Voulez-vous vraiment vider votre panier ?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger); border-color: #fecaca;">
                            <i class="bi bi-trash"></i> Vider le panier
                        </button>
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $item): 
                                $itemTotal = $item['price'] * $item['quantity'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="cart-item-info">
                                            <img src="<?= htmlspecialchars(format_image_url($item['image_url'])) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                                            <div>
                                                <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">
                                                    <a href="product-detail.php?id=<?= $item['id'] ?>" style="color: var(--dark);"><?= htmlspecialchars($item['name']) ?></a>
                                                </h4>
                                                <span style="font-size: 0.8rem; color: var(--dark-muted);">Réf: ART-<?= str_pad($item['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600;">
                                        <?= format_price($item['price']) ?>
                                    </td>
                                    <td>
                                        <form action="cart-action.php" method="POST" style="display: inline-block;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <div class="qty-picker" style="height: 38px;">
                                                <button type="submit" name="quantity" value="<?= $item['quantity'] - 1 ?>" class="qty-btn" style="height: 38px; width: 34px;"><i class="bi bi-dash"></i></button>
                                                <input type="number" readonly value="<?= $item['quantity'] ?>" class="qty-input" style="width: 38px; font-size: 0.9rem;">
                                                <button type="submit" name="quantity" value="<?= min($item['stock'] ?? 99, $item['quantity'] + 1) ?>" class="qty-btn" style="height: 38px; width: 34px;"><i class="bi bi-plus"></i></button>
                                            </div>
                                        </form>
                                    </td>
                                    <td style="font-weight: 800; color: var(--dark); font-family: var(--font-heading); font-size: 1.1rem;">
                                        <?= format_price($itemTotal) ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <form action="cart-action.php" method="POST" style="display: inline-block;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="action-btn" style="width: 34px; height: 34px; color: var(--danger);" title="Supprimer cet article">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Continuer mes achats
                    </a>
                </div>
            </div>

            <!-- Récapitulatif & Commande -->
            <div>
                <div class="order-summary">
                    <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--light-border);">
                        Récapitulatif de Commande
                    </h3>

                    <div class="summary-row">
                        <span>Sous-total articles</span>
                        <strong><?= format_price($totals['subtotal']) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Frais de livraison</span>
                        <?php if ($totals['shipping'] == 0): ?>
                            <span style="color: var(--success); font-weight: 700;">Gratuite (Offerte)</span>
                        <?php else: ?>
                            <span><?= format_price($totals['shipping']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="summary-row">
                        <span>Estimation TVA (16%)</span>
                        <span><?= format_price($totals['tax']) ?></span>
                    </div>

                    <?php if ($totals['discount'] > 0): ?>
                        <div class="summary-row" style="color: var(--success); font-weight: 600;">
                            <span>Remise code (<?= htmlspecialchars($totals['coupon_code']) ?>)</span>
                            <span>- <?= format_price($totals['discount']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="summary-row summary-total">
                        <span>Total à régler</span>
                        <span style="color: var(--primary);"><?= format_price($totals['total']) ?></span>
                    </div>

                    <!-- Formulaire Code Promotionnel -->
                    <div style="margin: 1.5rem 0; padding: 1.25rem; background: var(--light); border-radius: var(--radius-md); border: 1px solid var(--light-border);">
                        <?php if (empty($totals['coupon_code'])): ?>
                            <form action="cart-action.php" method="POST">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="apply_coupon">
                                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.45rem;">
                                    Code promo ou bon d'achat :
                                </label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" name="coupon_code" placeholder="Ex: PROMO10" class="form-control" style="text-transform: uppercase; font-size: 0.85rem;" required>
                                    <button type="submit" class="btn btn-dark btn-sm">Appliquer</button>
                                </div>
                                <small style="display: block; margin-top: 0.4rem; color: var(--dark-muted); font-size: 0.75rem;">Astuce : Utilisez <strong>PROMO10</strong> pour -10%</small>
                            </form>
                        <?php else: ?>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <i class="bi bi-tag-fill" style="color: var(--success);"></i>
                                    <strong><?= htmlspecialchars($totals['coupon_code']) ?></strong> appliqué !
                                </div>
                                <form action="cart-action.php" method="POST">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="remove_coupon">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Retirer</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bouton Passer la commande -->
                    <a href="checkout.php" class="btn btn-primary btn-block btn-lg" id="proceedCheckoutBtn">
                        Passer la commande <i class="bi bi-arrow-right"></i>
                    </a>

                    <div style="margin-top: 1.25rem; text-align: center; font-size: 0.8rem; color: var(--dark-muted);">
                        <i class="bi bi-shield-lock"></i> Paiement 100% sécurisé avec chiffrement SSL
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
