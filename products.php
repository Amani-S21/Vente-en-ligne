<?php
$pageTitle = "Catalogue & Articles - NovaStore";
require_once __DIR__ . '/includes/header.php';

// Récupération des filtres
$search = trim(sanitize($_GET['q'] ?? ''));
$categorySlug = trim(sanitize($_GET['category'] ?? ''));
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 3000;
$inStockOnly = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';
$sort = trim(sanitize($_GET['sort'] ?? 'featured'));

// Construction de la requête SQL sécurisée avec Prepared Statements PDO
$sql = "
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (LOWER(p.name) LIKE ? OR LOWER(p.description) LIKE ? OR LOWER(p.short_desc) LIKE ? OR LOWER(c.name) LIKE ?)";
    $likeSearch = '%' . mb_strtolower($search, 'UTF-8') . '%';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

if ($categorySlug !== '') {
    $sql .= " AND c.slug = ?";
    $params[] = $categorySlug;
}

if ($minPrice > 0) {
    $sql .= " AND p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice < 3000) {
    $sql .= " AND p.price <= ?";
    $params[] = $maxPrice;
}

if ($inStockOnly) {
    $sql .= " AND p.stock > 0";
}

// Tri
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.rating DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.created_at DESC, p.id DESC";
        break;
    default:
        $sql .= " ORDER BY p.is_featured DESC, p.rating DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Récupération de toutes les catégories avec le total d'articles
$allCats = $pdo->query("SELECT c.*, COUNT(p.id) as count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC")->fetchAll();
?>

<div class="container">
    <!-- Fil d'ariane -->
    <div style="padding: 1.25rem 0 0.5rem; font-size: 0.85rem; color: var(--dark-muted);">
        <a href="index.php">Accueil</a> &bull; <span>Catalogue des articles</span>
        <?php if ($categorySlug): ?>
            &bull; <strong style="color: var(--dark);"><?= htmlspecialchars($categorySlug) ?></strong>
        <?php endif; ?>
    </div>

    <div class="catalog-layout">
        <!-- Barre latérale de filtres -->
        <aside class="filter-sidebar">
            <form action="products.php" method="GET" id="filterForm">
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>

                <div class="filter-title">
                    <span><i class="bi bi-funnel-fill"></i> Filtres</span>
                    <a href="products.php" style="font-size: 0.8rem; font-weight: normal; color: var(--accent);">Réinitialiser</a>
                </div>

                <!-- Filtre Catégories -->
                <div class="filter-group">
                    <h4>Catégories</h4>
                    <ul class="filter-list">
                        <li class="filter-item">
                            <label>
                                <input type="radio" name="category" value="" <?= $categorySlug === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                                <span>Toutes les catégories</span>
                            </label>
                        </li>
                        <?php foreach ($allCats as $cat): ?>
                            <li class="filter-item">
                                <label>
                                    <input type="radio" name="category" value="<?= htmlspecialchars($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <span><?= htmlspecialchars($cat['name']) ?> (<?= $cat['count'] ?>)</span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Filtre Prix -->
                <div class="filter-group">
                    <h4>Prix ($)</h4>
                    <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem;">
                        <input type="number" name="min_price" value="<?= $minPrice ?>" min="0" max="3000" placeholder="Min" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                        <span>à</span>
                        <input type="number" name="max_price" value="<?= $maxPrice ?>" min="0" max="3000" placeholder="Max" class="form-control" style="padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                    </div>
                </div>

                <!-- Disponibilité en stock -->
                <div class="filter-group">
                    <h4>Disponibilité</h4>
                    <label class="filter-item" style="cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
                        <span style="font-size: 0.875rem;">En stock uniquement</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-sm" style="margin-top: 1rem;">
                    <i class="bi bi-check2"></i> Appliquer les filtres
                </button>
            </form>
        </aside>

        <!-- Zone d'affichage des articles -->
        <main>
            <!-- Barre de tri et résumé -->
            <div class="catalog-header">
                <div>
                    <strong><?= count($products) ?></strong> article(s) trouvé(s)
                    <?php if ($search !== ''): ?>
                        pour la recherche <span style="color: var(--primary);">"<?= htmlspecialchars($search) ?>"</span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <label for="sortSelect" style="font-size: 0.875rem; color: var(--dark-muted);">Trier par :</label>
                    <select id="sortSelect" class="sort-select" onchange="const url = new URL(window.location.href); url.searchParams.set('sort', this.value); window.location.href = url.toString();">
                        <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Recommandés</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix : Croissant</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix : Décroissant</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Mieux notés</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Nouveautés</option>
                    </select>
                </div>
            </div>

            <!-- Grille des produits -->
            <?php if (empty($products)): ?>
                <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-lg); padding: 4rem 2rem; text-align: center; margin-top: 1rem;">
                    <div style="font-size: 3.5rem; color: var(--light-muted); margin-bottom: 1rem;">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3>Aucun article ne correspond à vos critères</h3>
                    <p style="color: var(--dark-muted); max-width: 450px; margin: 0.5rem auto 1.5rem;">
                        Essayez de modifier ou de réinitialiser vos filtres de recherche pour découvrir l'ensemble de notre catalogue.
                    </p>
                    <a href="products.php" class="btn btn-primary"><i class="bi bi-arrow-counterclockwise"></i> Voir tous les articles</a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
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
                                    <?php if ($product['stock'] <= 0): ?>
                                        <span class="badge-tag badge-stock">Rupture</span>
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

                                    <?php if ($product['stock'] > 0): ?>
                                        <form action="cart-action.php" method="POST" class="ajax-add-to-cart">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-primary btn-sm" title="Ajouter au panier">
                                                <i class="bi bi-cart-plus"></i> Ajouter
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.6; cursor: not-allowed;">
                                            Épuisé
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
