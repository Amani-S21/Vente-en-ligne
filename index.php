<?php
$pageTitle = "NovaStore - Vente en Ligne & Technologie Premium";
require_once __DIR__ . '/includes/header.php';

// Récupérer les produits en vedette (Featured)
$featuredStmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_featured = 1 
    ORDER BY p.rating DESC 
    LIMIT 4
");
$featuredProducts = $featuredStmt->fetchAll();

// Récupérer les nouveautés
$newStmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_new = 1 
    ORDER BY p.id DESC 
    LIMIT 4
");
$newProducts = $newStmt->fetchAll();

// Récupérer les catégories avec le nombre de produits
$catCountStmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY product_count DESC
");
$categoriesList = $catCountStmt->fetchAll();
?>

<!-- ========================================================
     HERO BANNER MODERNE
     ======================================================== -->
<section class="hero-section">
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>
    <div class="container hero-grid">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="bi bi-stars"></i> Nouvelle Collection <?= date('Y') ?> &bull; Jusqu'à -30%
            </div>
            <h1 class="hero-title">
                L'Excellence du <span>Shopping en Ligne</span> Réinventée
            </h1>
            <p class="hero-desc">
                Découvrez des articles high-tech d'élite, des équipements audio haute fidélité et les dernières tendances mode avec une garantie d'authenticité et un suivi de colis en direct à Goma (Nord-Kivu) et dans toute la région.
            </p>

            <!-- Barre de recherche rapide de la page d'accueil -->
            <form action="products.php" method="GET" class="hero-search-box" style="margin: 1.5rem 0 2rem; max-width: 540px;">
                <div style="display: flex; gap: 0.5rem; background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(12px); padding: 0.45rem; border-radius: var(--radius-full); box-shadow: 0 15px 30px rgba(0,0,0,0.3); border: 2px solid rgba(255,255,255,0.4);">
                    <input type="text" name="q" placeholder="Quel article recherchez-vous ? (ex: iPhone, MacBook, Sony...)" required style="flex: 1; border: none; outline: none; background: transparent; padding: 0.65rem 1.25rem; font-family: var(--font-main); font-size: 0.95rem; color: var(--dark);">
                    <button type="submit" class="btn btn-primary" style="border-radius: var(--radius-full); padding: 0.65rem 1.5rem; font-weight: 700; white-space: nowrap;">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                </div>
                <div style="margin-top: 0.75rem; font-size: 0.8rem; color: #cbd5e1; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <span style="font-weight: 600;"><i class="bi bi-fire" style="color: #f59e0b;"></i> Tendances :</span>
                    <a href="products.php?q=MacBook" style="color: #a5b4fc; text-decoration: underline;">MacBook</a>
                    <a href="products.php?q=iPhone" style="color: #a5b4fc; text-decoration: underline;">iPhone 15</a>
                    <a href="products.php?q=Sony" style="color: #a5b4fc; text-decoration: underline;">Casque Sony</a>
                    <a href="products.php?q=Sneakers" style="color: #a5b4fc; text-decoration: underline;">Sneakers</a>
                    <a href="products.php?q=Montre" style="color: #a5b4fc; text-decoration: underline;">Montre Ultra</a>
                </div>
            </form>

            <div class="hero-cta">
                <a href="products.php" class="btn btn-primary btn-lg" id="heroExploreBtn">
                    <i class="bi bi-cart3"></i> Explorer la boutique
                </a>
                <a href="track.php" class="btn btn-secondary btn-lg" id="heroTrackBtn">
                    <i class="bi bi-geo-alt"></i> Suivre un colis
                </a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-card">
                <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80" alt="MacBook Pro Haute Performance" class="hero-img">
                <div class="hero-floating-badge">
                    <i class="bi bi-patch-check-fill"></i>
                    <div>
                        <div>Produits 100% Authentiques</div>
                        <small style="color: var(--dark-muted); font-weight: normal;">Garantie constructeur 24 mois</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================
     SIGNES DE CONFIANCE & AVANTAGES CLIENTS
     ======================================================== -->
<section class="features-bar">
    <div class="container">
        <div class="features-wrapper">
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-truck"></i></div>
                <div class="feature-info">
                    <h4>Livraison Rapide</h4>
                    <p>Expédition sous 24 à 48h avec suivi en temps réel</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-shield-lock"></i></div>
                <div class="feature-info">
                    <h4>Paiement Sécurisé</h4>
                    <p>Cartes bancaires, Visa et Mobile Money cryptés</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="feature-info">
                    <h4>Retour Garanti</h4>
                    <p>Satisfait ou remboursé sous 14 jours sans tracas</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-headset"></i></div>
                <div class="feature-info">
                    <h4>Support Dédié</h4>
                    <p>Assistance clientèle disponible 7j/7 par téléphone et chat</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================================================
     RAYONS & CATÉGORIES POPULAIRES
     ======================================================== -->
<section class="container" style="margin-bottom: 4rem;">
    <div class="section-header">
        <div>
            <h2 class="section-title">Parcourir par Catégories</h2>
            <p class="section-subtitle">Explorez nos sélections d'articles par univers thématiques</p>
        </div>
        <a href="products.php" class="btn btn-secondary btn-sm">Voir tout <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="categories-grid">
        <?php foreach ($categoriesList as $cat): ?>
            <a href="products.php?category=<?= htmlspecialchars($cat['slug']) ?>" class="category-card" id="cat-card-<?= htmlspecialchars($cat['slug']) ?>">
                <div class="cat-icon">
                    <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                </div>
                <h3><?= htmlspecialchars($cat['name']) ?></h3>
                <p><?= $cat['product_count'] ?> articles disponibles</p>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ========================================================
     PRODUITS EN VEDETTE (TOP VENTES)
     ======================================================== -->
<section class="container" style="margin-bottom: 4rem;">
    <div class="section-header">
        <div>
            <h2 class="section-title">Produits Coup de Cœur</h2>
            <p class="section-subtitle">Les articles les plus plébiscités par nos clients</p>
        </div>
        <a href="products.php" class="btn btn-secondary btn-sm">Catalogue complet <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <div class="product-thumb">
                    <img src="<?= htmlspecialchars(format_image_url($product['image_url'])) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                    <div class="product-badges">
                        <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                            <span class="badge-tag badge-promo">-<?= round((1 - ($product['price'] / $product['old_price'])) * 100) ?>%</span>
                        <?php endif; ?>
                        <?php if ($product['is_new']): ?>
                            <span class="badge-tag badge-new">Nouveau</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="product-body">
                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                    <h3 class="product-title">
                        <a href="product-detail.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>

                    <div class="product-rating">
                        <?php 
                        $fullStars = floor($product['rating']);
                        for ($i = 0; $i < 5; $i++): 
                            if ($i < $fullStars): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php else: ?>
                                <i class="bi bi-star"></i>
                            <?php endif; 
                        endfor; ?>
                        <span class="rating-count">(<?= $product['reviews_count'] ?>)</span>
                    </div>

                    <div class="product-footer">
                        <div class="product-price">
                            <span class="price-current"><?= format_price($product['price']) ?></span>
                            <?php if ($product['old_price']): ?>
                                <span class="price-old"><?= format_price($product['old_price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Formulaire d'ajout rapide au panier (AJAX) -->
                        <form action="cart-action.php" method="POST" class="ajax-add-to-cart">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary btn-sm" title="Ajouter au panier">
                                <i class="bi bi-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ========================================================
     BANNIÈRE PROMOTIONNELLE
     ======================================================== -->
<section class="container" style="margin-bottom: 4.5rem;">
    <div style="background: linear-gradient(135deg, #1e1b4b, #4338ca); border-radius: var(--radius-xl); padding: 3rem; color: white; display: grid; grid-template-columns: 1.2fr 0.8fr; align-items: center; gap: 2rem; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <span style="background: rgba(255,255,255,0.2); padding: 0.35rem 0.85rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">
                Offre Spéciale Limitée
            </span>
            <h2 style="color: white; font-size: 2.35rem; margin: 1rem 0 0.8rem; line-height: 1.2;">
                Code Promo Exclusif : <span style="color: #38bdf8;">PROMO10</span>
            </h2>
            <p style="color: #c7d2fe; font-size: 1.05rem; margin-bottom: 1.75rem; max-width: 500px;">
                Bénéficiez de 10% de réduction immédiate sur l'ensemble de votre commande avec le code promotionnel lors du paiement.
            </p>
            <a href="products.php" class="btn btn-secondary btn-lg">Profiter de l'offre maintenant</a>
        </div>
        <div style="text-align: center; position: relative; z-index: 2;">
            <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80" alt="Promo Audio" style="max-height: 260px; margin: 0 auto; border-radius: var(--radius-lg); box-shadow: 0 20px 35px rgba(0,0,0,0.4);">
        </div>
    </div>
</section>

<!-- ========================================================
     NOUVEAUTÉS DU MOMENT
     ======================================================== -->
<section class="container" style="margin-bottom: 4.5rem;">
    <div class="section-header">
        <div>
            <h2 class="section-title">Dernières Nouveautés</h2>
            <p class="section-subtitle">Tout juste arrivés dans nos entrepôts</p>
        </div>
        <a href="products.php?sort=newest" class="btn btn-secondary btn-sm">Toutes les nouveautés <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="products-grid">
        <?php foreach ($newProducts as $product): ?>
            <div class="product-card">
                <div class="product-thumb">
                    <img src="<?= htmlspecialchars(format_image_url($product['image_url'])) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                    <div class="product-badges">
                        <span class="badge-tag badge-new">Nouveau</span>
                    </div>
                </div>

                <div class="product-body">
                    <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                    <h3 class="product-title">
                        <a href="product-detail.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a>
                    </h3>

                    <div class="product-rating">
                        <?php 
                        $fullStars = floor($product['rating']);
                        for ($i = 0; $i < 5; $i++): 
                            if ($i < $fullStars): ?>
                                <i class="bi bi-star-fill"></i>
                            <?php else: ?>
                                <i class="bi bi-star"></i>
                            <?php endif; 
                        endfor; ?>
                        <span class="rating-count">(<?= $product['reviews_count'] ?>)</span>
                    </div>

                    <div class="product-footer">
                        <div class="product-price">
                            <span class="price-current"><?= format_price($product['price']) ?></span>
                        </div>

                        <form action="cart-action.php" method="POST" class="ajax-add-to-cart">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary btn-sm" title="Ajouter au panier">
                                <i class="bi bi-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
