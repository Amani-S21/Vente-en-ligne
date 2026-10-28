<?php
/**
 * Configuration et Connexion à la Base de Données (PDO)
 * Supporte SQLite (par défaut, sans configuration requise) et MySQL (phpMyAdmin/XAMPP/WAMP)
 */

// Configuration principale
define('DB_DRIVER', 'sqlite'); // 'sqlite' ou 'mysql'

// Configuration MySQL (si DB_DRIVER = 'mysql')
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'vente_en_ligne');
define('DB_USER', 'root');
define('DB_PASS', '');

// Chemin du fichier SQLite
define('SQLITE_FILE', __DIR__ . '/database.sqlite');

/**
 * Retourne l'instance PDO de connexion
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $dsn = 'sqlite:' . SQLITE_FILE;
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Activer les clés étrangères pour SQLite
            $pdo->exec('PRAGMA foreign_keys = ON;');
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        // Vérifier si la base de données est initialisée
        ensureDatabaseInitialized($pdo);

        return $pdo;
    } catch (PDOException $e) {
        die('<div style="font-family:sans-serif;padding:30px;background:#fee2e2;color:#991b1b;border-radius:8px;margin:30px auto;max-width:600px;border:1px solid #f87171;">
            <h2 style="margin-top:0;">Erreur de connexion à la base de données</h2>
            <p><strong>Message :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p>Si vous utilisez MySQL, vérifiez que le serveur MySQL (XAMPP/WAMP) est bien démarré et que la base "' . htmlspecialchars(DB_NAME) . '" existe.</p>
            <p>Ou passez <code>DB_DRIVER = \'sqlite\'</code> dans <code>config/database.php</code> pour une exécution immédiate.</p>
        </div>');
    }
}

/**
 * Initialisation automatique des tables et données de démonstration si absentes
 */
function ensureDatabaseInitialized(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    // Vérifier si la table products existe
    try {
        $result = $pdo->query("SELECT 1 FROM products LIMIT 1");
    } catch (Exception $e) {
        // La table n'existe pas encore, on initialise !
        require_once __DIR__ . '/init_db.php';
        initializeDatabase($pdo);
    }
}
