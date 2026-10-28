<?php
$pageTitle = "Paiement Sécurisé - NovaStore";
require_once __DIR__ . '/includes/header.php';

$cart = get_cart();
$totals = get_cart_totals();
$checkoutData = $_SESSION['checkout_data'] ?? null;

if (empty($cart) || empty($checkoutData)) {
    set_flash_message('warning', 'Veuillez renseigner vos coordonnées de livraison.');
    header('Location: checkout.php');
    exit;
}

// Frais de livraison finaux
$expressFee = $checkoutData['express_fee'] ?? 0.00;
$finalShipping = $totals['shipping'] + $expressFee;
$finalTotal = max(0, ($totals['subtotal'] - $totals['discount']) + $finalShipping + $totals['tax']);

// Traitement du paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'card');

        // Validation selon la méthode
        $isValidPayment = true;
        if ($paymentMethod === 'card') {
            $cardNumber = str_replace(' ', '', $_POST['card_number'] ?? '');
            if (strlen($cardNumber) < 16) {
                set_flash_message('danger', 'Numéro de carte bancaire incomplet (16 chiffres requis).');
                $isValidPayment = false;
            }
        } elseif ($paymentMethod === 'mobile_money') {
            $operator = sanitize($_POST['mm_operator'] ?? '');
            $mmPhone = sanitize($_POST['mm_phone'] ?? '');
            if (empty($mmPhone)) {
                set_flash_message('danger', 'Veuillez renseigner votre numéro de téléphone Mobile Money.');
                $isValidPayment = false;
            }
        }

        if ($isValidPayment) {
            try {
                $pdo->beginTransaction();

                // 1. Générer numéros uniques
                $orderNumber = 'CMD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $trackingCode = 'TRK-' . rand(1000, 9999) . '-CD';
                $userId = is_logged_in() ? $_SESSION['user_id'] : null;

                // 2. Insérer dans la table orders
                $orderStmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id, order_number, tracking_code, total_amount, shipping_fee, tax_amount, 
                        status, shipping_name, shipping_email, shipping_phone, shipping_address, 
                        shipping_city, payment_method, payment_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $initialStatus = ($paymentMethod === 'cash') ? 'pending' : 'paid';
                $paymentStatus = ($paymentMethod === 'cash') ? 'pending' : 'paid';

                $orderStmt->execute([
                    $userId,
                    $orderNumber,
                    $trackingCode,
                    $finalTotal,
                    $finalShipping,
                    $totals['tax'],
                    $initialStatus,
                    $checkoutData['shipping_name'],
                    $checkoutData['shipping_email'],
                    $checkoutData['shipping_phone'],
                    $checkoutData['shipping_address'],
                    $checkoutData['shipping_city'],
                    $paymentMethod,
                    $paymentStatus
                ]);

                $orderId = $pdo->lastInsertId();

                // 3. Insérer les articles dans order_items et décrémenter le stock
                $itemStmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total, image_url)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stockStmt = $pdo->prepare("UPDATE products SET stock = MAX(0, stock - ?) WHERE id = ?");

                foreach ($cart as $item) {
                    $lineTotal = $item['price'] * $item['quantity'];
                    $itemStmt->execute([
                        $orderId,
                        $item['id'],
                        $item['name'],
                        $item['price'],
                        $item['quantity'],
                        $lineTotal,
                        $item['image_url']
                    ]);

                    $stockStmt->execute([$item['quantity'], $item['id']]);
                }

                $pdo->commit();

                // Vider le panier et les données de session
                clear_cart();
                unset($_SESSION['checkout_data']);
                unset($_SESSION['coupon']);

                set_flash_message('success', 'Félicitations ! Votre commande a été enregistrée avec succès.');
                header('Location: order-confirmation.php?id=' . $orderId);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('danger', 'Une erreur est survenue lors de l\'enregistrement de la commande : ' . $e->getMessage());
            }
        }
    } else {
        set_flash_message('danger', 'Session expirée. Veuillez réessayer.');
    }
}
?>

<div class="container">
    <div style="padding: 1.5rem 0 0.5rem; font-size: 0.85rem; color: var(--dark-muted);">
        <a href="index.php">Accueil</a> &bull; 
        <a href="cart.php">Panier</a> &bull; 
        <a href="checkout.php">Livraison</a> &bull; 
        <span>Paiement sécurisé</span>
    </div>

    <!-- Stepper de progression -->
    <div style="display: flex; justify-content: center; margin: 1.5rem 0 2.5rem;">
        <div style="display: flex; align-items: center; gap: 1.5rem; max-width: 600px; width: 100%;">
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--success); font-weight: 700;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--success); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"><i class="bi bi-check-lg"></i></span>
                1. Panier
            </div>
            <div style="flex: 1; height: 3px; background: var(--success);"></div>
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--success); font-weight: 700;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--success); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"><i class="bi bi-check-lg"></i></span>
                2. Livraison
            </div>
            <div style="flex: 1; height: 3px; background: var(--primary);"></div>
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-weight: 700;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;">3</span>
                3. Paiement
            </div>
        </div>
    </div>

    <div class="cart-layout">
        <!-- Formulaire de Paiement -->
        <div>
            <div class="form-card">
                <h2 style="font-size: 1.35rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="bi bi-shield-check" style="color: var(--success);"></i> Choisissez votre Mode de Règlement
                </h2>

                <form action="payment.php" method="POST" id="paymentForm">
                    <?php csrf_field(); ?>

                    <!-- Choix du moyen de paiement -->
                    <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                        <!-- Option Carte Bancaire -->
                        <label style="border: 2px solid var(--primary); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer; background: var(--primary-light);">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="radio" name="payment_method" value="card" checked style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <div>
                                    <strong style="color: var(--dark); font-size: 1.05rem;">Carte Bancaire (Visa / MasterCard)</strong>
                                    <div style="font-size: 0.8rem; color: var(--dark-muted);">Paiement instantané et sécurisé par protocole 3D Secure</div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; font-size: 1.8rem; color: var(--primary);">
                                <i class="bi bi-credit-card"></i>
                            </div>
                        </label>

                        <!-- Option Mobile Money -->
                        <label style="border: 1px solid var(--light-border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="radio" name="payment_method" value="mobile_money" style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <div>
                                    <strong style="color: var(--dark); font-size: 1.05rem;">Mobile Money (M-Pesa, Orange Money, Airtel)</strong>
                                    <div style="font-size: 0.8rem; color: var(--dark-muted);">Validation directe par notification USSD sur votre smartphone</div>
                                </div>
                            </div>
                            <div style="font-size: 1.8rem; color: var(--warning);">
                                <i class="bi bi-phone"></i>
                            </div>
                        </label>

                        <!-- Option Paiement à la livraison -->
                        <label style="border: 1px solid var(--light-border); border-radius: var(--radius-md); padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <input type="radio" name="payment_method" value="cash" style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <div>
                                    <strong style="color: var(--dark); font-size: 1.05rem;">Paiement à la livraison (Espèces ou Terminal)</strong>
                                    <div style="font-size: 0.8rem; color: var(--dark-muted);">Réglez directement auprès du coursier à la réception du colis</div>
                                </div>
                            </div>
                            <div style="font-size: 1.8rem; color: var(--success);">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </label>
                    </div>

                    <!-- Champs Carte Bancaire -->
                    <div id="cardPaymentFields" style="background: var(--light); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--light-border); margin-bottom: 2rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-lock-fill" style="color: var(--primary);"></i> Informations de la Carte
                        </h4>

                        <div class="form-group">
                            <label class="form-label">Numéro de Carte Bancaire</label>
                            <input type="text" name="card_number" class="form-control" placeholder="4532 8900 1234 5678" maxlength="19" value="4532 8900 1234 5678">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Date d'expiration</label>
                                <input type="text" name="card_exp" class="form-control" placeholder="MM/AA" maxlength="5" value="12/28">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Cryptogramme (CVC/CVV)</label>
                                <input type="password" name="card_cvc" class="form-control" placeholder="123" maxlength="3" value="888">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Nom du Titulaire de la Carte</label>
                            <input type="text" name="card_holder" class="form-control" placeholder="Ex: JEAN LUC MUKENDI" value="<?= strtoupper(htmlspecialchars($checkoutData['shipping_name'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Champs Mobile Money -->
                    <div id="mobilePaymentFields" style="display: none; background: var(--light); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--light-border); margin-bottom: 2rem;">
                        <h4 style="font-size: 1rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="bi bi-phone-fill" style="color: var(--warning);"></i> Coordonnées Mobile Money
                        </h4>

                        <div class="form-group">
                            <label class="form-label">Opérateur Réseau</label>
                            <select name="mm_operator" class="form-control">
                                <option value="mpesa">Vodacom M-Pesa</option>
                                <option value="orange">Orange Money</option>
                                <option value="airtel">Airtel Money</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Numéro de Téléphone Lié au Compte</label>
                            <input type="tel" name="mm_phone" class="form-control" placeholder="Ex: +243 81 234 5678" value="<?= htmlspecialchars($checkoutData['shipping_phone'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" name="process_payment" class="btn btn-success btn-block btn-lg" id="confirmPaymentBtn">
                        <i class="bi bi-lock-fill"></i> Confirmer et Payer <?= format_price($finalTotal) ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Détails de Livraison & Montants -->
        <div>
            <div class="order-summary">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.25rem;">Récapitulatif Final</h3>

                <div style="background: var(--light); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.85rem; border: 1px solid var(--light-border);">
                    <div style="font-weight: 700; margin-bottom: 0.25rem;">Destinataire :</div>
                    <div><?= htmlspecialchars($checkoutData['shipping_name']) ?></div>
                    <div style="color: var(--dark-muted);"><?= htmlspecialchars($checkoutData['shipping_address']) ?>, <?= htmlspecialchars($checkoutData['shipping_city']) ?></div>
                    <div style="color: var(--dark-muted);"><?= htmlspecialchars($checkoutData['shipping_phone']) ?></div>
                    <div style="margin-top: 0.5rem; font-weight: 600; color: var(--primary);">
                        Mode : <?= $checkoutData['delivery_method'] === 'express' ? 'Livraison Express 24h (+10$)' : 'Livraison Standard' ?>
                    </div>
                </div>

                <div class="summary-row">
                    <span>Sous-total articles</span>
                    <strong><?= format_price($totals['subtotal']) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Frais d'expédition</span>
                    <span><?= format_price($finalShipping) ?></span>
                </div>

                <div class="summary-row">
                    <span>TVA (16%)</span>
                    <span><?= format_price($totals['tax']) ?></span>
                </div>

                <?php if ($totals['discount'] > 0): ?>
                    <div class="summary-row" style="color: var(--success); font-weight: 600;">
                        <span>Remise promo</span>
                        <span>- <?= format_price($totals['discount']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="summary-row summary-total">
                    <span>Montant Total Débité</span>
                    <span style="color: var(--success);"><?= format_price($finalTotal) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
