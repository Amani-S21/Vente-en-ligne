<?php
/**
 * Fonctions de Sécurité, Gestion de Session, Authentification et Panier
 */

// Démarrage sécurisé de la session si non démarrée
if (session_status() === PHP_SESSION_NONE) {
    // Configuration des paramètres de cookies de session sécurisés
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    // Si HTTPS actif
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

/**
 * Nettoie une chaîne de caractères contre les failles XSS
 */
function sanitize(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un jeton CSRF unique pour sécuriser les formulaires
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Affiche l'input hidden du jeton CSRF
 */
function csrf_field(): void {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

/**
 * Vérifie la validité du jeton CSRF soumis
 */
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Définit un message flash pour notification utilisateur
 */
function set_flash_message(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Récupère et vide les messages flash
 */
function get_flash_messages(): array {
    if (!isset($_SESSION['flash_messages'])) {
        return [];
    }
    $messages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Vérifie si un utilisateur est connecté
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté est administrateur
 */
function is_admin(): bool {
    return is_logged_in() && !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Récupère les données de l'utilisateur connecté depuis la session ou la BDD
 */
function get_current_user_data(?PDO $pdo = null): ?array {
    if (!is_logged_in()) {
        return null;
    }
    if ($pdo !== null) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, role, phone, address, city, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Utilisateur',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer'
    ];
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté
 */
function require_login(string $redirect = 'login.php'): void {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Veuillez vous connecter pour accéder à cette page.');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Bloque l'accès si l'utilisateur n'est pas administrateur
 */
function require_admin(string $redirect = '../index.php'): void {
    if (!is_admin()) {
        set_flash_message('danger', 'Accès réservé exclusivement aux administrateurs.');
        header('Location: ' . $redirect);
        exit;
    }
}

// ==========================================
// GESTION DU PANIER D'ACHAT (SESSION)
// ==========================================

/**
 * Récupère le contenu du panier
 */
function get_cart(): array {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Ajoute un produit au panier
 */
function add_to_cart(int $productId, string $name, float $price, int $quantity = 1, string $imageUrl = '', int $stock = 99): void {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $newQty = $_SESSION['cart'][$productId]['quantity'] + $quantity;
        if ($newQty > $stock) {
            $newQty = $stock;
        }
        $_SESSION['cart'][$productId]['quantity'] = $newQty;
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => $productId,
            'name' => $name,
            'price' => $price,
            'quantity' => min($quantity, $stock),
            'image_url' => $imageUrl,
            'stock' => $stock
        ];
    }
}

/**
 * Modifie la quantité d'un article dans le panier
 */
function update_cart_quantity(int $productId, int $quantity): void {
    if (isset($_SESSION['cart'][$productId])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $maxStock = $_SESSION['cart'][$productId]['stock'] ?? 99;
            $_SESSION['cart'][$productId]['quantity'] = min($quantity, $maxStock);
        }
    }
}

/**
 * Supprime un article du panier
 */
function remove_from_cart(int $productId): void {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
}

/**
 * Vide intégralement le panier
 */
function clear_cart(): void {
    $_SESSION['cart'] = [];
}

/**
 * Calcule les totaux du panier (sous-total, livraison, taxe, remise, total)
 */
function get_cart_totals(): array {
    $cart = get_cart();
    $subtotal = 0.0;
    $itemCount = 0;

    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $itemCount += $item['quantity'];
    }

    // Frais de livraison (offerts si sous-total > 200$, sinon 15$)
    $shipping = ($subtotal > 200 || $subtotal == 0) ? 0.00 : 15.00;

    // Calcul de la taxe (ex: TVA 16% indicative)
    $taxRate = 0.16;
    $tax = round($subtotal * $taxRate, 2);

    // Code promo appliqué
    $discount = 0.0;
    if (!empty($_SESSION['coupon'])) {
        if ($_SESSION['coupon']['type'] === 'percent') {
            $discount = round($subtotal * ($_SESSION['coupon']['value'] / 100), 2);
        } else {
            $discount = min($subtotal, $_SESSION['coupon']['value']);
        }
    }

    $total = max(0, ($subtotal - $discount) + $shipping + $tax);

    return [
        'subtotal' => $subtotal,
        'item_count' => $itemCount,
        'shipping' => $shipping,
        'tax' => $tax,
        'discount' => $discount,
        'coupon_code' => $_SESSION['coupon']['code'] ?? null,
        'total' => $total
    ];
}

/**
 * Formate un montant en devise (ex: 2 499,00 $)
 */
function format_price(float $amount): string {
    return number_format($amount, 2, ',', ' ') . ' $';
}

/**
 * Résout correctement l'URL d'une image qu'elle soit externe (http) ou locale sur le serveur
 */
function format_image_url(?string $url, string $pathPrefix = ''): string {
    if (empty($url)) {
        return 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80';
    }
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
    }
    return $pathPrefix . ltrim($url, '/');
}
