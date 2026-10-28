<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = getDBConnection();
$productId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    $pageTitle = "Article non trouvé - NovaStore";
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding: 5rem 0; text-align: center;">
            <i class="bi bi-exclamation-circle" style="font-size: 3.5rem; color: var(--danger);"></i>
            <h2 style="margin: 1.5rem 0 1rem;">Oups ! Article introuvable</h2>
            <p style="color: var(--dark-muted); margin-bottom: 2rem;">L\'article que vous recherchez n\'existe pas ou a été retiré de la vente.</p>
            <a href="products.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Retour au catalogue</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Traitement de l'ajout d'avis client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $reviewerName = trim(sanitize($_POST['user_name'] ?? 'Client'));
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim(sanitize($_POST['comment'] ?? ''));

        if (!empty($comment)) {
            $revStmt = $pdo->prepare("INSERT INTO reviews (product_id, user_name, rating, comment) VALUES (?, ?, ?, ?)");
            $revStmt->execute([$productId, $reviewerName, $rating, $comment]);

            // Mettre à jour la note moyenne du produit
            $calcStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as count FROM reviews WHERE product_id = ?");
            $calcStmt->execute([$productId]);
            $stats = $calcStmt->fetch();

            $upStmt = $pdo->prepare("UPDATE products SET rating = ?, reviews_count = ? WHERE id = ?");
            $upStmt->execute([round($stats['avg_rating'], 1), $stats['count'], $productId]);

            set_flash_message('success', 'Votre avis a été publié avec succès. Merci pour votre retour !');
            header('Location: product-detail.php?id=' . $productId . '#reviewsSection');
            exit;
        }
    }
}

// Récupérer les avis du produit
$revQuery = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
$revQuery->execute([$productId]);
$reviews = $revQuery->fetchAll();

// Récupérer les produits similaires (même catégorie)
$relatedStmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 3");
$relatedStmt->execute([$product['category_id'], $productId]);
$relatedProducts = $relatedStmt->fetchAll();

$pageTitle = $product['name'] . " - NovaStore";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Fil d'ariane -->
    <div style="padding: 1.5rem 0 0.5rem; font-size: 0.85rem; color: var(--dark-muted);">
        <a href="index.php">Accueil</a> &bull; 
        <a href="products.php">Catalogue</a> &bull; 
        <a href="products.php?category=<?= htmlspecialchars($product['category_slug']) ?>"><?= htmlspecialchars($product['category_name']) ?></a> &bull; 
        <span style="color: var(--dark); font-weight: 600;"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <!-- Disposition Détail Produit -->
    <div class="detail-layout">
        <!-- Galerie Produit -->
        <div class="detail-gallery">
            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="detail-main-img" id="mainProductImage">
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid var(--primary); cursor: pointer;">
            </div>
        </div>

        <!-- Informations & Achat -->
        <div class="detail-info">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                
                <!-- Badge Disponibilité -->
                <?php if ($product['stock'] > 5): ?>
                    <span class="detail-stock-badge in-stock"><i class="bi bi-check-circle-fill"></i> En Stock (<?= $product['stock'] ?> ex.)</span>
                <?php elseif ($product['stock'] > 0): ?>
                    <span class="detail-stock-badge low-stock"><i class="bi bi-clock-history"></i> Stock limité (Plus que <?= $product['stock'] ?>)</span>
                <?php else: ?>
                    <span class="detail-stock-badge out-of-stock"><i class="bi bi-x-circle-fill"></i> Épuisé</span>
                <?php endif; ?>
            </div>

            <h1 style="font-size: 2rem; margin-bottom: 0.85rem;"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Évaluations -->
            <div class="product-rating" style="margin-bottom: 1.25rem;">
                <?php 
                $fullStars = floor($product['rating']);
                for ($i = 0; $i < 5; $i++): 
                    if ($i < $fullStars): ?>
                        <i class="bi bi-star-fill"></i>
                    <?php else: ?>
                        <i class="bi bi-star"></i>
                    <?php endif; 
                endfor; ?>
                <span style="font-weight: 700; color: var(--dark); margin-left: 0.35rem;"><?= $product['rating'] ?>/5</span>
                <a href="#reviewsSection" class="rating-count" style="text-decoration: underline;">(<?= count($reviews) ?> avis vérifiés)</a>
            </div>

            <!-- Court extrait -->
            <p style="font-size: 1.05rem; color: #475569; line-height: 1.6; margin-bottom: 1.5rem;">
                <?= htmlspecialchars($product['short_desc']) ?>
            </p>

            <!-- Prix -->
            <div class="detail-price-box">
                <span class="detail-price-main"><?= format_price($product['price']) ?></span>
                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                    <span style="font-size: 1.15rem; color: var(--light-muted); text-decoration: line-through;">
                        <?= format_price($product['old_price']) ?>
                    </span>
                    <span class="badge-tag badge-promo" style="font-size: 0.85rem;">
                        Économisez <?= format_price($product['old_price'] - $product['price']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Formulaire d'ajout au panier avec choix de la quantité -->
            <?php if ($product['stock'] > 0): ?>
                <form action="cart-action.php" method="POST" class="ajax-add-to-cart">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="detail-actions">
                        <div class="qty-picker">
                            <button type="button" class="qty-btn" data-action="decrease"><i class="bi bi-dash"></i></button>
                            <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="qty-input">
                            <button type="button" class="qty-btn" data-action="increase"><i class="bi bi-plus"></i></button>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" style="flex: 1;" id="detailAddToCartBtn">
                            <i class="bi bi-cart3"></i> Ajouter au panier
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 1rem 1.25rem; border-radius: var(--radius-md); margin: 1.5rem 0; font-weight: 600;">
                    <i class="bi bi-bell"></i> Cet article est temporairement épuisé. Revenez très bientôt !
                </div>
            <?php endif; ?>

            <!-- Engagements / Réassurance -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--light-border);">
                <div style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.85rem;">
                    <i class="bi bi-shield-check" style="font-size: 1.25rem; color: var(--primary);"></i>
                    <span>Garantie 2 ans constructeur</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.85rem;">
                    <i class="bi bi-arrow-clockwise" style="font-size: 1.25rem; color: var(--primary);"></i>
                    <span>Retours gratuits 14 jours</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.85rem;">
                    <i class="bi bi-truck" style="font-size: 1.25rem; color: var(--primary);"></i>
                    <span>Expédition sous 24-48h</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.85rem;">
                    <i class="bi bi-lock" style="font-size: 1.25rem; color: var(--primary);"></i>
                    <span>Transactions chiffrées SSL</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Description complète & Caractéristiques -->
    <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2.5rem; margin-bottom: 3.5rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.25rem;">Description Détaillée du Produit</h2>
        <div style="color: #334155; line-height: 1.8; font-size: 1rem;">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
    </div>

    <!-- Section Avis Clients -->
    <div id="reviewsSection" style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2.5rem; margin-bottom: 4rem;">
        <div class="section-header">
            <div>
                <h2 class="section-title">Avis et Commentaires Clients</h2>
                <p class="section-subtitle">Ce que pensent nos acheteurs vérifiés</p>
            </div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">
                <?= $product['rating'] ?> <span style="font-size: 1rem; color: var(--dark-muted);">/ 5</span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3rem;">
            <!-- Liste des avis -->
            <div>
                <?php if (empty($reviews)): ?>
                    <p style="color: var(--dark-muted); font-style: italic;">Soyez le premier à donner votre avis sur cet article !</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div style="padding: 1.25rem 0; border-bottom: 1px solid var(--light-border);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                <strong><?= htmlspecialchars($rev['user_name']) ?></strong>
                                <span style="font-size: 0.8rem; color: var(--dark-muted);"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                            </div>
                            <div class="product-rating" style="margin-bottom: 0.5rem;">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="bi bi-star<?= $s <= $rev['rating'] ? '-fill' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p style="color: #475569; font-size: 0.95rem; margin: 0;"><?= htmlspecialchars($rev['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Formulaire pour laisser un avis -->
            <div style="background: var(--light); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--light-border); height: fit-content;">
                <h3 style="font-size: 1.15rem; margin-bottom: 1rem;">Donnez votre avis</h3>
                <form action="product-detail.php?id=<?= $productId ?>" method="POST">
                    <?php csrf_field(); ?>
                    <div class="form-group">
                        <label class="form-label">Votre nom complet</label>
                        <input type="text" name="user_name" required class="form-control" placeholder="Ex: Joseph K." value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Note générale</label>
                        <select name="rating" class="form-control">
                            <option value="5">★★★★★ - Excellent (5/5)</option>
                            <option value="4">★★★★☆ - Très bon (4/5)</option>
                            <option value="3">★★★☆☆ - Correct (3/5)</option>
                            <option value="2">★★☆☆☆ - Moyen (2/5)</option>
                            <option value="1">★☆☆☆☆ - Décevant (1/5)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Votre commentaire d'expérience</label>
                        <textarea name="comment" rows="3" required class="form-control" placeholder="Partagez vos impressions sur le produit..."></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="btn btn-primary btn-block btn-sm">
                        <i class="bi bi-send"></i> Publier mon avis
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Produits similaires recommandés -->
    <?php if (!empty($relatedProducts)): ?>
        <section style="margin-bottom: 4rem;">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Dans la même catégorie</h2>
                    <p class="section-subtitle">Ces articles pourraient également vous intéresser</p>
                </div>
            </div>

            <div class="products-grid">
                <?php foreach ($relatedProducts as $rel): ?>
                    <div class="product-card">
                        <div class="product-thumb">
                            <img src="<?= htmlspecialchars($rel['image_url']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" loading="lazy">
                        </div>
                        <div class="product-body">
                            <h3 class="product-title">
                                <a href="product-detail.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a>
                            </h3>
                            <div class="product-footer">
                                <span class="price-current"><?= format_price($rel['price']) ?></span>
                                <a href="product-detail.php?id=<?= $rel['id'] ?>" class="btn btn-secondary btn-sm">Voir l'article</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
