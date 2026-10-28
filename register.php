<?php
$pageTitle = "Créer un Compte Client - NovaStore";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: profile.php');
    exit;
}

$pdo = getDBConnection();
$fullName = '';
$email = '';
$phone = '';
$city = 'Kinshasa';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $fullName = trim(sanitize($_POST['full_name'] ?? ''));
        $email = trim(sanitize($_POST['email'] ?? ''));
        $phone = trim(sanitize($_POST['phone'] ?? ''));
        $city = trim(sanitize($_POST['city'] ?? 'Kinshasa'));
        $address = trim(sanitize($_POST['address'] ?? ''));
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];

        if (empty($fullName)) $errors[] = "Le nom complet est obligatoire.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Veuillez renseigner une adresse email valide.";
        if (strlen($password) < 6) $errors[] = "Le mot de passe doit comporter au moins 6 caractères.";
        if ($password !== $passwordConfirm) $errors[] = "Les deux mots de passe ne correspondent pas.";

        // Vérifier si l'email existe déjà
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $errors[] = "Un compte existe déjà avec cette adresse e-mail.";
            }
        }

        if (empty($errors)) {
            // Hachage sécurisé du mot de passe avec l'algorithme BCRYPT
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password, role, phone, address, city) 
                VALUES (?, ?, ?, 'customer', ?, ?, ?)
            ");
            $insertStmt->execute([$fullName, $email, $hashedPassword, $phone, $address, $city]);
            $newUserId = $pdo->lastInsertId();

            // Connexion immédiate de l'utilisateur
            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'customer';

            set_flash_message('success', 'Votre compte a été créé avec succès ! Bienvenue sur NovaStore.');
            header('Location: profile.php');
            exit;
        } else {
            foreach ($errors as $err) {
                set_flash_message('danger', $err);
            }
        }
    } else {
        set_flash_message('danger', 'Jeton de formulaire expiré, veuillez réessayer.');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="margin: 3rem auto 5rem;">
    <div class="form-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; border-radius: var(--radius-md); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem;">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <h1 style="font-size: 1.85rem; margin-bottom: 0.4rem;">Création de Compte</h1>
            <p style="color: var(--dark-muted); font-size: 0.95rem;">Rejoignez la communauté NovaStore et profitez d'avantages exclusifs</p>
        </div>

        <form action="register.php" method="POST" id="registerForm">
            <?php csrf_field(); ?>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom et Prénom *</label>
                    <input type="text" name="full_name" required class="form-control" placeholder="Ex: Joseph Kabila" value="<?= htmlspecialchars($fullName) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Numéro de Téléphone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Ex: +243 81 000 0000" value="<?= htmlspecialchars($phone) ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Adresse Email *</label>
                <input type="email" name="email" required class="form-control" placeholder="votre.email@domaine.com" value="<?= htmlspecialchars($email) ?>" autocomplete="email">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Ville de résidence</label>
                    <input type="text" name="city" class="form-control" placeholder="Ex: Kinshasa" value="<?= htmlspecialchars($city) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Adresse / Quartier</label>
                    <input type="text" name="address" class="form-control" placeholder="Ex: Gombe, Avenue de la Libération" value="<?= htmlspecialchars($address) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mot de passe (Min. 6 caractères) *</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="regPassword" name="password" required minlength="6" class="form-control" placeholder="••••••••" style="padding-right: 2.8rem;">
                        <button type="button" class="toggle-password-btn" data-target="regPassword" style="position: absolute; right: 0.75rem; background: none; border: none; color: var(--dark-muted); cursor: pointer; font-size: 1.1rem;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer le mot de passe *</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="regPasswordConfirm" name="password_confirm" required minlength="6" class="form-control" placeholder="••••••••" style="padding-right: 2.8rem;">
                        <button type="button" class="toggle-password-btn" data-target="regPasswordConfirm" style="position: absolute; right: 0.75rem; background: none; border: none; color: var(--dark-muted); cursor: pointer; font-size: 1.1rem;">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin: 1.25rem 0;">
                <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-size: 0.85rem; color: var(--dark-muted);">
                    <input type="checkbox" required style="width: 17px; height: 17px; accent-color: var(--primary);">
                    <span>J'accepte les conditions générales d'utilisation et la politique de confidentialité.</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitRegisterBtn">
                <i class="bi bi-person-check"></i> Créer mon compte
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.9rem; color: var(--dark-muted);">
            Vous avez déjà un compte ? 
            <a href="login.php" style="font-weight: 700;">Connectez-vous ici</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
