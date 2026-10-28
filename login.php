<?php
$pageTitle = "Connexion à votre Espace - NovaStore";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Si déjà connecté, rediriger
if (is_logged_in()) {
    header('Location: profile.php');
    exit;
}

$pdo = getDBConnection();
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $email = trim(sanitize($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            set_flash_message('danger', 'Veuillez remplir tous les champs obligatoires.');
        } else {
            // Requête préparée pour éviter toute injection SQL
            $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Régénération de l'ID de session pour prévenir le Session Fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                set_flash_message('success', 'Bienvenue, ' . $user['full_name'] . ' ! Vous êtes connecté.');

                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: profile.php');
                }
                exit;
            } else {
                set_flash_message('danger', 'Adresse email ou mot de passe incorrect.');
            }
        }
    } else {
        set_flash_message('danger', 'Session expirée, veuillez actualiser la page.');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="margin: 3.5rem auto 5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; border-radius: var(--radius-md); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem;">
                <i class="bi bi-person-lock"></i>
            </div>
            <h1 style="font-size: 1.85rem; margin-bottom: 0.4rem;">Espace Client Sécurisé</h1>
            <p style="color: var(--dark-muted); font-size: 0.95rem;">Connectez-vous pour gérer vos commandes et vos coordonnées</p>
        </div>

        <form action="login.php" method="POST" novalidate id="loginForm">
            <?php csrf_field(); ?>

            <div class="form-group">
                <label class="form-label">Adresse Email *</label>
                <div style="position: relative;">
                    <input type="email" name="email" required class="form-control" placeholder="nom@exemple.com" value="<?= htmlspecialchars($email) ?>" autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                    <label class="form-label" style="margin-bottom: 0;">Mot de passe *</label>
                    <a href="#" onclick="alert('Pour réinitialiser votre mot de passe, contactez l\'assistance ou utilisez les comptes de démonstration.'); return false;" style="font-size: 0.8rem;">Mot de passe oublié ?</a>
                </div>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="loginPassword" name="password" required class="form-control" placeholder="••••••••" style="padding-right: 2.8rem;">
                    <button type="button" class="toggle-password-btn" data-target="loginPassword" style="position: absolute; right: 0.75rem; background: none; border: none; color: var(--dark-muted); cursor: pointer; font-size: 1.1rem;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 1.5rem;" id="submitLoginBtn">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
            </button>
        </form>

        <!-- Comptes de démonstration pré-configurés pour tester instantanément -->
        <div style="background: var(--light); border: 1px dashed var(--light-border); border-radius: var(--radius-md); padding: 1.25rem; margin-top: 2rem; font-size: 0.85rem;">
            <div style="font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                <i class="bi bi-key-fill" style="color: var(--primary);"></i> Comptes de test pour l'évaluation :
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" onclick="document.querySelector('[name=email]').value='client@boutique.com'; document.getElementById('loginPassword').value='password123';" class="btn btn-secondary btn-sm" style="flex: 1;">
                    <i class="bi bi-person"></i> Remplir Client Démo
                </button>
                <button type="button" onclick="document.querySelector('[name=email]').value='admin@boutique.com'; document.getElementById('loginPassword').value='admin123';" class="btn btn-secondary btn-sm" style="flex: 1;">
                    <i class="bi bi-shield-lock"></i> Remplir Admin Démo
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: var(--dark-muted);">
            Pas encore de compte ? 
            <a href="register.php" style="font-weight: 700;">Créer un compte gratuitement</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
