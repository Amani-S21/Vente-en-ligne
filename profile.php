<?php
$pageTitle = "Mon Profil & Mes Commandes - NovaStore";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login('login.php');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];

// Traitement de la mise à jour des coordonnées
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $fullName = trim(sanitize($_POST['full_name'] ?? ''));
        $phone = trim(sanitize($_POST['phone'] ?? ''));
        $address = trim(sanitize($_POST['address'] ?? ''));
        $city = trim(sanitize($_POST['city'] ?? ''));

        if (!empty($fullName)) {
            $upStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, city = ? WHERE id = ?");
            $upStmt->execute([$fullName, $phone, $address, $city, $userId]);
            $_SESSION['user_name'] = $fullName;
            set_flash_message('success', 'Vos coordonnées ont été mises à jour avec succès.');
        }
    }
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $chkStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $chkStmt->execute([$userId]);
        $row = $chkStmt->fetch();

        if ($row && password_verify($oldPass, $row['password'])) {
            if (strlen($newPass) >= 6 && $newPass === $confirmPass) {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
                set_flash_message('success', 'Votre mot de passe a été modifié avec succès.');
            } else {
                set_flash_message('danger', 'Le nouveau mot de passe doit comporter au moins 6 caractères et correspondre à la confirmation.');
            }
        } else {
            set_flash_message('danger', 'Votre mot de passe actuel est erroné.');
        }
    }
}

// Récupérer les informations à jour
$user = get_current_user_data($pdo);

// Récupérer l'historique des commandes du client
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$orderStmt->execute([$userId]);
$userOrders = $orderStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 2.5rem auto 5rem;">
    <!-- En-tête profil -->
    <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2.5rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; box-shadow: 0 8px 20px var(--primary-glow);">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($user['full_name']) ?></h1>
                <div style="color: var(--dark-muted); font-size: 0.9rem;">
                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?> &bull; 
                    <span class="badge-tag" style="background: <?= $user['role'] === 'admin' ? 'var(--primary)' : 'var(--dark-muted)' ?>; color: white;">
                        <?= $user['role'] === 'admin' ? 'Administrateur' : 'Client Vérifié' ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <?php if (is_admin()): ?>
                <a href="admin/index.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-speedometer2"></i> Tableau de bord Admin
                </a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </div>
    </div>

    <!-- Grille des sections : Commandes & Édition profil -->
    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem;">
        <!-- Historique des commandes -->
        <div>
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h2 style="font-size: 1.35rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-bag-check" style="color: var(--primary);"></i> Mes Commandes Récentes (<?= count($userOrders) ?>)
                </h2>

                <?php if (empty($userOrders)): ?>
                    <div style="text-align: center; padding: 3rem 1rem;">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: var(--light-muted);"></i>
                        <h4 style="margin: 1rem 0 0.5rem;">Aucune commande pour le moment</h4>
                        <p style="color: var(--dark-muted); font-size: 0.9rem; margin-bottom: 1.25rem;">Explorez notre catalogue et profitez de la livraison offerte dès 200 $ !</p>
                        <a href="products.php" class="btn btn-primary btn-sm">Découvrir les articles</a>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($userOrders as $ord): ?>
                            <div style="border: 1px solid var(--light-border); border-radius: var(--radius-md); padding: 1.25rem; transition: all 0.2s ease;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                    <div>
                                        <strong style="font-size: 1.05rem;"><?= htmlspecialchars($ord['order_number']) ?></strong>
                                        <div style="font-size: 0.8rem; color: var(--dark-muted);"><?= date('d/m/Y à H:i', strtotime($ord['created_at'])) ?></div>
                                    </div>
                                    <span class="badge-tag" style="background: <?= $ord['status'] === 'delivered' ? 'var(--success)' : ($ord['status'] === 'shipped' ? 'var(--info)' : 'var(--warning)') ?>;">
                                        <?= strtoupper($ord['status']) ?>
                                    </span>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 0.75rem; margin-top: 0.75rem; font-size: 0.9rem;">
                                    <div>
                                        <span>Code de suivi : <strong style="color: var(--primary);"><?= htmlspecialchars($ord['tracking_code']) ?></strong></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <span style="font-weight: 800; font-family: var(--font-heading);"><?= format_price($ord['total_amount']) ?></span>
                                        <a href="track.php?code=<?= urlencode($ord['tracking_code']) ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-geo-alt"></i> Suivre
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Paramètres du profil & mot de passe -->
        <div>
            <!-- Formulaire Coordonnées -->
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-pencil-square" style="color: var(--primary);"></i> Modifier mes coordonnées
                </h3>

                <form action="profile.php" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="update_profile" value="1">

                    <div class="form-group">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="full_name" required class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ville</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adresse de livraison</label>
                        <textarea name="address" rows="2" class="form-control"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-sm">
                        Enregistrer les modifications
                    </button>
                </form>
            </div>

            <!-- Formulaire Mot de Passe -->
            <div style="background: white; border: 1px solid var(--light-border); border-radius: var(--radius-xl); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-shield-lock" style="color: var(--primary);"></i> Sécurité du Compte
                </h3>

                <form action="profile.php" method="POST">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="update_password" value="1">

                    <div class="form-group">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" name="old_password" required class="form-control" placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nouveau mot de passe (min. 6 car.)</label>
                        <input type="password" name="new_password" required minlength="6" class="form-control" placeholder="••••••••">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" required minlength="6" class="form-control" placeholder="••••••••">
                    </div>

                    <button type="submit" class="btn btn-secondary btn-block btn-sm">
                        Changer le mot de passe
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
