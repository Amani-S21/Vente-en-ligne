<?php
// Initialisation de la base de données et de l'authentification
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDBConnection();
$cartTotals = get_cart_totals();
$cartCount = $cartTotals['item_count'];

// Récupérer les catégories pour le menu
try {
    $catMenuStmt = $pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY name ASC");
    $navCategories = $catMenuStmt->fetchAll();
} catch (Exception $e) {
    $navCategories = [];
}

$currentUser = get_current_user_data($pdo);
$pageTitle = $pageTitle ?? 'NovaStore - Boutique en Ligne Moderne';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Découvrez NovaStore, votre destination e-commerce premium pour les articles high-tech, mode, audio et maison au meilleur prix.">
    <meta name="theme-color" content="#4f46e5">

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Feuille de style personnalisée -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Barre supérieure informative -->
    <div class="top-bar">
        <div class="container">
            <div>
                <i class="bi bi-shield-check"></i> Paiement 100% Sécurisé &bull; Livraison Express &bull; Retours sous 14 jours
            </div>
            <div class="top-bar-links">
                <a href="track.php"><i class="bi bi-geo-alt"></i> Suivre ma commande</a>
                <a href="tel:+243810000000"><i class="bi bi-telephone"></i> Support : +243 81 000 000</a>
            </div>
        </div>
    </div>

    <!-- En-tête Principal & Navigation -->
    <header class="site-header">
        <div class="container">
            <div class="navbar">
                <!-- Logo de la marque -->
                <a href="index.php" class="brand-logo" id="brandLogo">
                    <div class="logo-icon">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <span>Nova<span>Store</span></span>
                </a>

                <!-- Formulaire de recherche dynamique -->
                <form action="products.php" method="GET" class="header-search" id="headerSearchForm">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="q" placeholder="Rechercher un produit, une marque, un modèle..." value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>" autocomplete="off" required>
                </form>

                <!-- Liens de navigation -->
                <nav>
                    <ul class="nav-links">
                        <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="bi bi-house"></i> Accueil</a></li>
                        <li><a href="products.php" class="<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>"><i class="bi bi-grid"></i> Catalogue</a></li>
                        <li><a href="track.php" class="<?= basename($_SERVER['PHP_SELF']) === 'track.php' ? 'active' : '' ?>"><i class="bi bi-truck"></i> Suivi Colis</a></li>
                        <?php if (is_admin()): ?>
                            <li><a href="admin/index.php" style="color: #4f46e5; font-weight: 700;"><i class="bi bi-speedometer2"></i> Administration</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Actions utilisateur (Panier & Compte) -->
                <div class="header-actions">
                    <!-- Bouton Panier -->
                    <a href="cart.php" class="action-btn" title="Voir mon panier" id="navCartBtn">
                        <i class="bi bi-bag"></i>
                        <span class="action-badge cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none;' ?>"><?= $cartCount ?></span>
                    </a>

                    <!-- Bouton Compte / Connexion -->
                    <?php if (is_logged_in()): ?>
                        <a href="profile.php" class="action-btn" title="Mon Compte : <?= htmlspecialchars($currentUser['full_name'] ?? '') ?>" id="navProfileBtn">
                            <i class="bi bi-person-check-fill" style="color: var(--primary);"></i>
                        </a>
                        <a href="logout.php" class="action-btn" title="Se déconnecter" id="navLogoutBtn">
                            <i class="bi bi-box-arrow-right"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-sm" id="navLoginBtn">
                            <i class="bi bi-person"></i> Connexion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Zone d'affichage des alertes et messages flash -->
    <div class="container alert-container">
        <?php foreach (get_flash_messages() as $flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                <?php if ($flash['type'] === 'success'): ?>
                    <i class="bi bi-check-circle-fill"></i>
                <?php elseif ($flash['type'] === 'danger'): ?>
                    <i class="bi bi-exclamation-triangle-fill"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle-fill"></i>
                <?php endif; ?>
                <div><?= htmlspecialchars($flash['message']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
