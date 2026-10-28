<?php
/**
 * Traitement des actions du panier (Ajout, modification, suppression, coupons)
 * Compatible requêtes AJAX (JSON) et formulaires classiques (POST)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

// Vérification CSRF
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Jeton de sécurité CSRF expiré ou invalide.']);
        exit;
    }
    set_flash_message('danger', 'Jeton de sécurité invalide.');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($productId > 0) {
            $stmt = $pdo->prepare("SELECT id, name, price, stock, image_url FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if ($product) {
                if ($product['stock'] <= 0) {
                    $msg = 'Désolé, cet article est actuellement en rupture de stock.';
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => $msg]);
                        exit;
                    }
                    set_flash_message('warning', $msg);
                } else {
                    add_to_cart($product['id'], $product['name'], (float)$product['price'], $quantity, $product['image_url'], (int)$product['stock']);
                    $totals = get_cart_totals();
                    $msg = 'L\'article "' . $product['name'] . '" a bien été ajouté au panier !';

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => $msg,
                            'cart_count' => $totals['item_count'],
                            'subtotal' => $totals['subtotal']
                        ]);
                        exit;
                    }
                    set_flash_message('success', $msg);
                }
            }
        }
        break;

    case 'update':
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        update_cart_quantity($productId, $quantity);
        set_flash_message('info', 'Quantité mise à jour.');
        break;

    case 'remove':
        $productId = (int)($_POST['product_id'] ?? 0);
        remove_from_cart($productId);
        set_flash_message('info', 'Article retiré du panier.');
        break;

    case 'clear':
        clear_cart();
        set_flash_message('info', 'Votre panier a été vidé.');
        break;

    case 'apply_coupon':
        $code = strtoupper(trim(sanitize($_POST['coupon_code'] ?? '')));
        if ($code === 'PROMO10') {
            $_SESSION['coupon'] = [
                'code' => 'PROMO10',
                'type' => 'percent',
                'value' => 10,
                'description' => 'Réduction de 10% sur votre commande'
            ];
            set_flash_message('success', 'Code promo "PROMO10" appliqué avec succès (-10%) !');
        } elseif ($code === 'WELCOME20') {
            $_SESSION['coupon'] = [
                'code' => 'WELCOME20',
                'type' => 'fixed',
                'value' => 20,
                'description' => 'Réduction de 20 $ de bienvenue'
            ];
            set_flash_message('success', 'Code promo "WELCOME20" appliqué (-20 $) !');
        } else {
            set_flash_message('danger', 'Code promo invalide ou expiré.');
        }
        break;

    case 'remove_coupon':
        unset($_SESSION['coupon']);
        set_flash_message('info', 'Code promo retiré.');
        break;
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
header('Location: ' . $redirect);
exit;
