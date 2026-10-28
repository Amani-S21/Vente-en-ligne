<?php
$pageTitle = "Administration - Gestion des Articles - NovaStore";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('../login.php');

$pdo = getDBConnection();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['edit'] ?? 0);
$editProduct = null;

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch();
    if ($editProduct) {
        $action = 'edit';
    }
}

// Traitement POST (Création, Édition, Suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $postAction = $_POST['post_action'] ?? '';

        if ($postAction === 'save') {
            $prodId = (int)($_POST['id'] ?? 0);
            $catId = (int)($_POST['category_id'] ?? 1);
            $name = trim(sanitize($_POST['name'] ?? ''));
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $shortDesc = trim(sanitize($_POST['short_desc'] ?? ''));
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $oldPrice = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
            $stock = (int)($_POST['stock'] ?? 0);
            $imageUrl = trim($_POST['image_url'] ?? '');
            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
            $isNew = isset($_POST['is_new']) ? 1 : 0;

            if (empty($imageUrl)) {
                $imageUrl = 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80';
            }

            if ($prodId > 0) {
                // Mise à jour
                $up = $pdo->prepare("
                    UPDATE products SET 
                        category_id = ?, name = ?, short_desc = ?, description = ?, 
                        price = ?, old_price = ?, stock = ?, image_url = ?, 
                        is_featured = ?, is_new = ? 
                    WHERE id = ?
                ");
                $up->execute([$catId, $name, $shortDesc, $description, $price, $oldPrice, $stock, $imageUrl, $isFeatured, $isNew, $prodId]);
                set_flash_message('success', 'Article mis à jour avec succès.');
            } else {
                // Insertion
                // Vérifier unicité du slug
                $slugCheck = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
                $slugCheck->execute([$slug]);
                if ($slugCheck->fetch()) {
                    $slug .= '-' . rand(10, 99);
                }

                $ins = $pdo->prepare("
                    INSERT INTO products (category_id, name, slug, short_desc, description, price, old_price, stock, image_url, is_featured, is_new)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$catId, $name, $slug, $shortDesc, $description, $price, $oldPrice, $stock, $imageUrl, $isFeatured, $isNew]);
                set_flash_message('success', 'Nouvel article ajouté au catalogue.');
            }

            header('Location: products.php');
            exit;

        } elseif ($postAction === 'delete') {
            $delId = (int)($_POST['id'] ?? 0);
            if ($delId > 0) {
                $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $del->execute([$delId]);
                set_flash_message('info', 'Article supprimé définitivement du catalogue.');
            }
            header('Location: products.php');
            exit;
        }
    }
}

// Liste de tous les produits
$allProducts = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-nav { background: #0f172a; color: white; padding: 1rem 0; }
        .admin-nav .container { display: flex; justify-content: space-between; align-items: center; }
        .admin-menu { display: flex; gap: 1.5rem; list-style: none; }
        .admin-menu a { color: #94a3b8; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; }
        .admin-menu a.active, .admin-menu a:hover { color: white; }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <!-- Navigation Admin -->
    <header class="admin-nav">
        <div class="container">
            <a href="../index.php" class="brand-logo" style="color: white; font-size: 1.25rem;">
                <div class="logo-icon" style="width: 32px; height: 32px; font-size: 1rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <span>Nova<span>Admin</span></span>
            </a>

            <ul class="admin-menu">
                <li><a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="products.php" class="active"><i class="bi bi-box-seam"></i> Produits</a></li>
                <li><a href="orders.php"><i class="bi bi-receipt"></i> Commandes</a></li>
                <li><a href="../index.php" target="_blank"><i class="bi bi-shop"></i> Voir la boutique</a></li>
                <li><a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
            </ul>
        </div>
    </header>

    <div class="container" style="margin: 2.5rem auto 5rem;">
        <!-- Alertes -->
        <?php foreach (get_flash_messages() as $f): ?>
            <div class="alert alert-<?= $f['type'] ?>"><?= htmlspecialchars($f['message']) ?></div>
        <?php endforeach; ?>

        <?php if ($action === 'create' || $action === 'edit'): ?>
            <!-- Formulaire Ajout / Édition -->
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2.5rem; max-width: 850px; margin: 0 auto; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="font-size: 1.5rem;"><?= $action === 'edit' ? 'Modifier l\'article' : 'Ajouter un nouvel article' ?></h2>
                    <a href="products.php" class="btn btn-secondary btn-sm"><i class="bi bi-x-lg"></i> Annuler</a>
                </div>

                <form action="products.php" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="post_action" value="save">
                    <input type="hidden" name="id" value="<?= $editProduct['id'] ?? 0 ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Titre du Produit *</label>
                            <input type="text" name="name" required class="form-control" placeholder="Ex: Casque Sony Sans Fil" value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Catégorie de rattachement *</label>
                            <select name="category_id" required class="form-control">
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($editProduct['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Accroche courte (Résumé pour cartes) *</label>
                        <input type="text" name="short_desc" required class="form-control" placeholder="Ex: Autonomie 30h, réduction de bruit active..." value="<?= htmlspecialchars($editProduct['short_desc'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description détaillée et caractéristiques *</label>
                        <textarea name="description" rows="5" required class="form-control" placeholder="Détaillez les fonctionnalités, composants, dimensions et points forts..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Prix de vente ($) *</label>
                            <input type="number" step="0.01" name="price" required class="form-control" placeholder="299.00" value="<?= $editProduct['price'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Ancien prix (Barré) ($)</label>
                            <input type="number" step="0.01" name="old_price" class="form-control" placeholder="349.00" value="<?= $editProduct['old_price'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Stock disponible (Unités) *</label>
                            <input type="number" name="stock" required min="0" class="form-control" placeholder="15" value="<?= $editProduct['stock'] ?? 10 ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">URL de l'image (Haute résolution)</label>
                        <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/photo-..." value="<?= htmlspecialchars($editProduct['image_url'] ?? '') ?>">
                    </div>

                    <div style="display: flex; gap: 2rem; margin: 1.5rem 0;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_featured" value="1" <?= (!empty($editProduct['is_featured'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>Mettre en vedette (Coup de cœur page d'accueil)</span>
                        </label>

                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_new" value="1" <?= (!empty($editProduct['is_new'])) ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>Badge "Nouveauté"</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="bi bi-save"></i> Enregistrer l'article
                    </button>
                </form>
            </div>

        <?php else: ?>
            <!-- Liste des produits -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 2rem;">Catalogue des Articles (<?= count($allProducts) ?>)</h1>
                    <p style="color: var(--dark-muted); font-size: 0.95rem;">Gérez vos références de produits, prix et inventaires</p>
                </div>
                <a href="products.php?action=create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Créer un nouvel article
                </a>
            </div>

            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="overflow-x: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Désignation</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Note</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allProducts as $p): ?>
                                <tr>
                                    <td>
                                        <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width: 50px; height: 50px; border-radius: var(--radius-sm); object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['name']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--dark-muted);">
                                            <?= $p['is_featured'] ? '<span class="badge-tag badge-promo">Vedette</span>' : '' ?>
                                            <?= $p['is_new'] ? '<span class="badge-tag badge-new">Nouveau</span>' : '' ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                                    <td style="font-weight: 700;"><?= format_price($p['price']) ?></td>
                                    <td>
                                        <?php if ($p['stock'] > 5): ?>
                                            <span class="badge-tag" style="background: #dcfce7; color: #166534;"><?= $p['stock'] ?> en stock</span>
                                        <?php elseif ($p['stock'] > 0): ?>
                                            <span class="badge-tag" style="background: #fef3c7; color: #92400e;"><?= $p['stock'] ?> restant</span>
                                        <?php else: ?>
                                            <span class="badge-tag" style="background: #fee2e2; color: #991b1b;">Rupture</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="color: #eab308; font-weight: 700;">★ <?= $p['rating'] ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="products.php" method="POST" onsubmit="return confirm('Confirmer la suppression de cet article ?');">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="post_action" value="delete">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
