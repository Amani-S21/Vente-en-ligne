<?php
$pageTitle = "Finalisation de Commande - NovaStore";
require_once __DIR__ . '/includes/header.php';

$cart = get_cart();
$totals = get_cart_totals();

if (empty($cart)) {
    set_flash_message('warning', 'Votre panier est vide. Ajoutez des articles avant de passer commande.');
    header('Location: products.php');
    exit;
}

$userData = get_current_user_data($pdo);

// Traitement du formulaire de livraison
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $shippingName = trim(sanitize($_POST['shipping_name'] ?? ''));
        $shippingEmail = trim(sanitize($_POST['shipping_email'] ?? ''));
        $shippingPhone = trim(sanitize($_POST['shipping_phone'] ?? ''));
        $shippingAddress = trim(sanitize($_POST['shipping_address'] ?? ''));
        $shippingCity = trim(sanitize($_POST['shipping_city'] ?? ''));
        $deliveryMethod = trim(sanitize($_POST['delivery_method'] ?? 'standard'));

        $errors = [];
        if (empty($shippingName)) $errors[] = "Le nom du destinataire est requis.";
        if (empty($shippingEmail) || !filter_var($shippingEmail, FILTER_VALIDATE_EMAIL)) $errors[] = "Une adresse email valide est requise.";
        if (empty($shippingPhone)) $errors[] = "Le numéro de téléphone est requis pour la livraison.";
        if (empty($shippingAddress)) $errors[] = "L'adresse de livraison est requise.";
        if (empty($shippingCity)) $errors[] = "La ville de destination est requise.";

        if (empty($errors)) {
            // Sauvegarder dans la session pour l'étape de paiement
            $_SESSION['checkout_data'] = [
                'shipping_name' => $shippingName,
                'shipping_email' => $shippingEmail,
                'shipping_phone' => $shippingPhone,
                'shipping_address' => $shippingAddress,
                'shipping_city' => $shippingCity,
                'delivery_method' => $deliveryMethod,
                'express_fee' => ($deliveryMethod === 'express' ? 10.00 : 0.00)
            ];

            header('Location: payment.php');
            exit;
        } else {
            foreach ($errors as $err) {
                set_flash_message('danger', $err);
            }
        }
    } else {
        set_flash_message('danger', 'Session expirée, veuillez réessayer.');
    }
}
?>

<div class="container">
    <div style="padding: 1.5rem 0 0.5rem; font-size: 0.85rem; color: var(--dark-muted);">
        <a href="index.php">Accueil</a> &bull; 
        <a href="cart.php">Panier</a> &bull; 
        <span>Validation de commande</span>
    </div>

    <!-- Stepper de progression -->
    <div style="display: flex; justify-content: center; margin: 1.5rem 0 2.5rem;">
        <div style="display: flex; align-items: center; gap: 1.5rem; max-width: 600px; width: 100%;">
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--success); font-weight: 700;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--success); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"><i class="bi bi-check-lg"></i></span>
                1. Panier
            </div>
            <div style="flex: 1; height: 3px; background: var(--primary);"></div>
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-weight: 700;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">2</span>
                2. Livraison
            </div>
            <div style="flex: 1; height: 3px; background: #e2e8f0;"></div>
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--dark-muted); font-weight: 600;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; color: var(--dark-muted); display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">3</span>
                3. Paiement
            </div>
        </div>
    </div>

    <div class="cart-layout">
        <!-- Formulaire de Coordonnées & Livraison -->
        <div>
            <div class="form-card">
                <h2 style="font-size: 1.35rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="bi bi-geo-alt-fill" style="color: var(--primary);"></i> Adresse et Coordonnées de Livraison
                </h2>

                <form action="checkout.php" method="POST" id="checkoutForm">
                    <?php csrf_field(); ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nom et Prénom du destinataire *</label>
                            <input type="text" name="shipping_name" required class="form-control" placeholder="Ex: Jean-Luc Mukendi" value="<?= htmlspecialchars($_SESSION['checkout_data']['shipping_name'] ?? $userData['full_name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Numéro de téléphone *</label>
                            <input type="tel" name="shipping_phone" required class="form-control" placeholder="Ex: +243 81 234 5678" value="<?= htmlspecialchars($_SESSION['checkout_data']['shipping_phone'] ?? $userData['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adresse Email de confirmation *</label>
                        <input type="email" name="shipping_email" required class="form-control" placeholder="Ex: client@boutique.com" value="<?= htmlspecialchars($_SESSION['checkout_data']['shipping_email'] ?? $userData['email'] ?? '') ?>">
                        <small style="color: var(--dark-muted); font-size: 0.75rem;">Le récapitulatif et le code de suivi seront envoyés à cette adresse.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adresse complète de livraison (Rue, Quartier, N°) *</label>
                        <textarea name="shipping_address" required rows="2" class="form-control" placeholder="Ex: 45 Boulevard du 30 Juin, Résidence Elysée, Appartement 4B"><?= htmlspecialchars($_SESSION['checkout_data']['shipping_address'] ?? $userData['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ville de livraison *</label>
                            <input type="text" name="shipping_city" required class="form-control" placeholder="Ex: Kinshasa" value="<?= htmlspecialchars($_SESSION['checkout_data']['shipping_city'] ?? $userData['city'] ?? 'Kinshasa') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pays</label>
                            <input type="text" readonly class="form-control" value="RDC / International" style="background: #e2e8f0; cursor: not-allowed;">
                        </div>
                    </div>

                    <!-- Choix du Mode de Livraison -->
                    <h3 style="font-size: 1.15rem; margin: 2rem 0 1rem;">Option d'Expédition</h3>

                    <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                        <label style="border: 1px solid var(--light-border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="radio" name="delivery_method" value="standard" checked style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <div>
                                    <strong>Livraison Standard Suivie</strong>
                                    <div style="font-size: 0.8rem; color: var(--dark-muted);">Délai estimé : 2 à 4 jours ouvrés</div>
                                </div>
                            </div>
                            <span style="font-weight: 700; color: var(--success);">
                                <?= $totals['shipping'] == 0 ? 'Gratuite' : format_price($totals['shipping']) ?>
                            </span>
                        </label>

                        <label style="border: 1px solid var(--light-border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="radio" name="delivery_method" value="express" style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <div>
                                    <strong>Livraison Express 24h Chrono</strong>
                                    <div style="font-size: 0.8rem; color: var(--dark-muted);">Remise en main propre dès le lendemain</div>
                                </div>
                            </div>
                            <span style="font-weight: 700; color: var(--primary);">+ 10,00 $</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitCheckoutBtn">
                        Continuer vers le Paiement Sécurisé <i class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Récapitulatif Rapide Commande -->
        <div>
            <div class="order-summary">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Votre Commande</h3>
                
                <div style="max-height: 280px; overflow-y: auto; margin-bottom: 1.5rem; padding-right: 0.5rem;">
                    <?php foreach ($cart as $item): ?>
                        <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--light-border);">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" style="width: 50px; height: 50px; border-radius: var(--radius-sm); object-fit: cover;">
                            <div style="flex: 1;">
                                <h5 style="font-size: 0.9rem; margin-bottom: 0.2rem;"><?= htmlspecialchars($item['name']) ?></h5>
                                <span style="font-size: 0.8rem; color: var(--dark-muted);">Qté : <?= $item['quantity'] ?> &times; <?= format_price($item['price']) ?></span>
                            </div>
                            <strong><?= format_price($item['price'] * $item['quantity']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Sous-total</span>
                    <strong><?= format_price($totals['subtotal']) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Frais de port de base</span>
                    <span><?= $totals['shipping'] == 0 ? 'Offerts' : format_price($totals['shipping']) ?></span>
                </div>

                <div class="summary-row">
                    <span>Estimation Taxe (16%)</span>
                    <span><?= format_price($totals['tax']) ?></span>
                </div>

                <?php if ($totals['discount'] > 0): ?>
                    <div class="summary-row" style="color: var(--success); font-weight: 600;">
                        <span>Code promo (<?= htmlspecialchars($totals['coupon_code']) ?>)</span>
                        <span>- <?= format_price($totals['discount']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row summary-total">
                    <span>Total Estimé</span>
                    <span style="color: var(--primary);"><?= format_price($totals['total']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
