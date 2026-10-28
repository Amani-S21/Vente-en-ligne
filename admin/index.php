<?php
$pageTitle = "Administration - Tableau de Bord - NovaStore";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('../login.php');

$pdo = getDBConnection();

// Statistiques globales
$totalSales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0.00;
$totalOrders = $pdo->query("SELECT COUNT(id) FROM orders")->fetchColumn() ?: 0;
$totalProducts = $pdo->query("SELECT COUNT(id) FROM products")->fetchColumn() ?: 0;
$totalUsers = $pdo->query("SELECT COUNT(id) FROM users WHERE role = 'customer'")->fetchColumn() ?: 0;

// Dernières commandes
$recentOrders = $pdo->query("
    SELECT o.*, u.full_name as client_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC 
    LIMIT 6
")->fetchAll();

// Produits avec stock faible
$lowStockProducts = $pdo->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 4")->fetchAll();
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
        .admin-nav {
            background: #0f172a;
            color: white;
            padding: 1rem 0;
        }
        .admin-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-menu {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }
        .admin-menu a {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .admin-menu a.active, .admin-menu a:hover {
            color: white;
        }
        .stat-card {
            background: white;
            border: 1px solid var(--light-border);
            border-radius: var(--radius-xl);
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
    </style>
</head>
<body style="background-color: #f8fafc;">

    <!-- Navigation Admin -->
    <header class="admin-nav">
        <div class="container">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="../index.php" class="brand-logo" style="color: white; font-size: 1.25rem;">
                    <div class="logo-icon" style="width: 32px; height: 32px; font-size: 1rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <span>Nova<span>Admin</span></span>
                </a>
            </div>

            <ul class="admin-menu">
                <li><a href="index.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="bi bi-box-seam"></i> Produits</a></li>
                <li><a href="orders.php"><i class="bi bi-receipt"></i> Commandes</a></li>
                <li><a href="../index.php" target="_blank"><i class="bi bi-shop"></i> Voir la boutique</a></li>
                <li><a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
            </ul>
        </div>
    </header>

    <div class="container" style="margin: 2.5rem auto 5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem;">Tableau de Bord Administrateur</h1>
                <p style="color: var(--dark-muted); font-size: 0.95rem;">Vue d'ensemble des performances commerciales et gestion de la boutique</p>
            </div>
            <div>
                <a href="products.php?action=create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Ajouter un article
                </a>
            </div>
        </div>

        <!-- 4 Cartes Métriques Clés -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0e7ff; color: #4338ca;">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--dark-muted); font-weight: 600;">Chiffre d'Affaires</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--dark); font-family: var(--font-heading);"><?= format_price($totalSales) ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #15803d;">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--dark-muted); font-weight: 600;">Commandes Totales</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--dark); font-family: var(--font-heading);"><?= $totalOrders ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--dark-muted); font-weight: 600;">Articles en Vente</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--dark); font-family: var(--font-heading);"><?= $totalProducts ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #b45309;">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--dark-muted); font-weight: 600;">Clients Inscrits</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: var(--dark); font-family: var(--font-heading);"><?= $totalUsers ?></div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem;">
            <!-- Dernières Commandes -->
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="font-size: 1.25rem;">Commandes Récentes</h3>
                    <a href="orders.php" class="btn btn-secondary btn-sm">Gérer toutes</a>
                </div>

                <div style="overflow-x: auto;">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ord): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($ord['order_number']) ?></strong>
                                        <div style="font-size: 0.75rem; color: var(--dark-muted);"><?= htmlspecialchars($ord['tracking_code']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($ord['shipping_name']) ?></td>
                                    <td style="font-weight: 700;"><?= format_price($ord['total_amount']) ?></td>
                                    <td>
                                        <span class="badge-tag" style="background: <?= $ord['status'] === 'delivered' ? 'var(--success)' : ($ord['status'] === 'shipped' ? 'var(--info)' : 'var(--warning)') ?>; font-size: 0.7rem;">
                                            <?= strtoupper($ord['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?view=<?= $ord['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">
                                            Détail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alertes Stocks Faibles -->
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm); height: fit-content;">
                <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-exclamation-triangle" style="color: var(--warning);"></i> Alertes Stock
                </h3>

                <?php if (empty($lowStockProducts)): ?>
                    <p style="color: var(--success); font-weight: 600;">Tous vos stocks sont optimaux !</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($lowStockProducts as $low): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid var(--light-border); padding-bottom: 0.75rem;">
                                <img src="<?= htmlspecialchars($low['image_url']) ?>" style="width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover;">
                                <div style="flex: 1;">
                                    <div style="font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($low['name']) ?></div>
                                    <span style="font-size: 0.75rem; color: <?= $low['stock'] == 0 ? 'var(--danger)' : 'var(--warning)' ?>; font-weight: 700;">
                                        Reste : <?= $low['stock'] ?> unité(s)
                                    </span>
                                </div>
                                <a href="products.php?edit=<?= $low['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                    Réappro
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
