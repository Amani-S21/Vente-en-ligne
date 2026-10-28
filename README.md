# NovaStore - Logiciel de Vente en Ligne d'Articles (E-Commerce)

Ce projet est une solution logicielle complète et professionnelle de **vente en ligne d'articles**, conçue dans le cadre du travail pratique (TP) académique en respectant rigoureusement le cahier des charges et les spécifications technologiques.

---

## 🚀 Spécifications Technologiques & Architecture

Conformément à la fiche de spécification :

| Composant | Technologie Utilisée | Rôle dans l'application |
| :--- | :--- | :--- |
| **Structure** | **HTML5 Sémantique** | Balisage structuré, accessibilité, SEO, balises `<header>`, `<nav>`, `<main>`, `<section>`, `<footer>` |
| **Style & Design** | **CSS3 Moderne** | Palette sur mesure, Glassmorphism, Animations, Grilles Flexbox/CSS Grid, Responsive Design |
| **Typographie & Icônes** | **Plus Jakarta Sans / Outfit** & **Bootstrap Icons 1.11.3** | Rendu visuel haut de gamme et icônes vectorielles légères |
| **Dynamisme Client** | **JavaScript (ES6+)** | Ajout au panier asynchrone (AJAX), Toasts de notification dynamiques, Sélecteurs de quantité, Bascule de mot de passe et méthode de paiement |
| **Backend & Métier** | **PHP 8+ (Architecture MVC modulaire)** | Routage, gestion des sessions, authentification, contrôleurs panier et tunnel d'achat |
| **Persistance de Données**| **PDO (SQLite & MySQL)** | Double compatibilité : exécution instantanée zéro config (SQLite) + export SQL pour phpMyAdmin (MySQL) |

---

## 🛡️ Sécurité Implémentée

1. **Protection des Mots de Passe** : Hachage fort avec l'algorithme `BCRYPT` via les fonctions natives PHP `password_hash()` et `password_verify()`.
2. **Protection contre les Injections SQL** : Utilisation exclusive de **Requêtes Préparées PDO** avec liaison de paramètres (`prepare()` / `execute()`).
3. **Protection contre les Attaques CSRF** : Génération de jetons cryptographiques aléatoires (`bin2hex(random_bytes(32))`) et vérification systématique sur chaque formulaire (`hash_equals()`).
4. **Protection XSS** : Filtrage et échappement systématique des entrées/sorties utilisateur via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
5. **Protection de Session & HTTPS Ready** :
   - `session.cookie_httponly = 1` (Protection contre l'accès JavaScript aux cookies)
   - `session.cookie_samesite = Lax`
   - Régénération de l'identifiant de session lors de la connexion (`session_regenerate_id(true)`) pour empêcher le vol de session (Session Fixation).

---

## 🌟 Fonctionnalités Réalisées (100% Conformes au TP)

1. **Page d'Accueil (Landing Page)** :
   - Hero banner immersif avec appel à l'action
   - Avantages clients (Livraison express, Sécurité SSL, Retours 14j, Support 7j/7)
   - Rayons par catégories avec compteur d'articles
   - Grille des articles coup de cœur et dernières nouveautés
   - Bannière promotionnelle avec code promo actif
2. **Consultation d'Articles** :
   - Fiche produit détaillée avec galerie d'images haute résolution
   - Gestion du stock en temps réel (En stock / Stock faible / Rupture)
   - Système d'avis et de notation étoilée (1 à 5 étoiles) avec formulaire de soumission
   - Recommandation d'articles similaires dans la même catégorie
3. **Recherche et Filtrage** :
   - Moteur de recherche instantané par mot-clé (nom, description)
   - Filtrage dynamique par catégorie
   - Filtrage par fourchette de prix
   - Filtrage des articles en stock
   - Tri intelligent (Recommandés, Prix croissant/décroissant, Note, Nouveautés)
4. **Inscription et Connexion** :
   - Création de compte client avec validation
   - Connexion sécurisée avec détection des rôles (Client / Administrateur)
   - Espace profil client pour modifier ses coordonnées et changer son mot de passe
   - Déconnexion sécurisée nettoyant session et cookies
5. **Panier d'Achat** :
   - Ajout au panier instantané sans rechargement de page (AJAX)
   - Modification des quantités (+ / -) et suppression d'articles
   - Application de coupons de réduction fonctionnels (`PROMO10` pour -10%, `WELCOME20` pour 20$ de remise)
   - Calcul automatique du sous-total, livraison (offerte dès 200$), TVA (16%) et Total
6. **Tunnel de Commande (Checkout)** :
   - Saisie et vérification des coordonnées et de l'adresse de livraison
   - Choix du transporteur (Standard économique ou Express 24h Chrono)
   - Récapitulatif direct de commande
7. **Paiement Sécurisé** :
   - Carte Bancaire (Visa / MasterCard) avec validation
   - Mobile Money (Vodacom M-Pesa, Orange Money, Airtel Money)
   - Paiement à la livraison (Cash on Delivery)
   - Génération d'une référence de commande (`CMD-2026-...`) et d'un code de suivi colis (`TRK-...`)
8. **Suivi des Commandes (Order Tracking)** :
   - Recherche publique par numéro de commande ou code de suivi
   - Timeline visuelle dynamique à 4 étapes : *Validée* ➔ *En Préparation* ➔ *Expédiée* ➔ *Livrée*
9. **Espace d'Administration (Back-Office)** :
   - Tableau de bord des statistiques (Chiffre d'affaires, Commandes, Produits, Clients)
   - Gestion CRUD complète des articles (Ajouter, Modifier, Supprimer, Stock)
   - Gestion et changement du statut d'acheminement des commandes en temps réel

---

## 🔑 Identifiants de Démonstration (Pour l'évaluation)

| Rôle | Adresse Email | Mot de passe | Description |
| :--- | :--- | :--- | :--- |
| **Client Démo** | `client@boutique.com` | `password123` | Compte client avec commandes déjà passées pour tester l'historique |
| **Administrateur**| `admin@boutique.com` | `admin123` | Accès complet au tableau de bord et à la gestion de la boutique |

### Codes de Suivi Colis Démo (Pour tester le suivi immédiatement) :
- `TRK-8921-CD` : Commande en cours d'acheminement (*Expédiée*)
- `TRK-4412-CD` : Commande terminée (*Livrée*)

---

## 💻 Instructions de Lancement

### Méthode 1 : Lancement Ultra-Rapide (Serveur Intégré PHP)
Ouvrez un terminal dans le dossier du projet (`f:\PROJECT WEB\VENTE EN LIGNE`) et lancez :
```bash
php -S 127.0.0.1:8000
```
Puis ouvrez votre navigateur sur : **[http://127.0.0.1:8000](http://127.0.0.1:8000)**.
> *Note : La base de données SQLite s'initialise automatiquement au premier chargement avec toutes les tables et données de démonstration !*

### Méthode 2 : Déploiement sur XAMPP / WampServer (MySQL / phpMyAdmin)
1. Copiez le dossier du projet dans le répertoire web :
   - XAMPP : `C:\xampp\htdocs\vente_en_ligne`
   - WAMP : `C:\wamp64\www\vente_en_ligne`
2. Ouvrez **phpMyAdmin** et importez le fichier :
   - `config/schema.sql`
3. Dans `config/database.php`, changez simplement :
   ```php
   define('DB_DRIVER', 'mysql');
   ```
4. Accédez au site via : `http://localhost/vente_en_ligne/`

---

## 📁 Arborescence Propre du Projet

```
VENTE EN LIGNE/
│
├── index.php                 # Page d'accueil (Landing Page)
├── products.php              # Catalogue avec recherche et filtres
├── product-detail.php        # Consultation détaillée et avis clients
├── cart.php                  # Panier d'achat avec coupons et calculs
├── cart-action.php           # Contrôleur d'actions panier (AJAX / POST)
├── checkout.php              # Tunnel de commande et coordonnées de livraison
├── payment.php               # Simulation de paiement sécurisé (CB, Mobile Money, Cash)
├── order-confirmation.php    # Page de reçu et confirmation avec code de suivi
├── track.php                 # Suivi de commande en temps réel avec stepper interactif
├── login.php                 # Authentification sécurisée
├── register.php              # Création de compte client
├── profile.php               # Espace client, coordonnées et historique
├── logout.php                # Déconnexion sécurisée
├── README.md                 # Documentation complète du projet
│
├── config/
│   ├── database.php          # Connexion PDO universelle (SQLite & MySQL)
│   ├── init_db.php           # Création automatique des tables et données de démo
│   └── schema.sql            # Script SQL MySQL pour phpMyAdmin / TP
│
├── includes/
│   ├── header.php            # Navbar moderne, barre de recherche, badge panier
│   ├── footer.php            # Pied de page riche avec réassurance et liens
│   └── auth.php              # Sécurité, CSRF, gestion de session et panier
│
├── admin/
│   ├── index.php             # Tableau de bord des statistiques (KPIs)
│   ├── products.php          # CRUD des produits (Création, modification, suppression)
│   └── orders.php            # Gestion et changement de statut des commandes
│
└── assets/
    ├── css/
    │   └── style.css         # Design system personnalisé, glassmorphism, responsive
    └── js/
        └── main.js           # AJAX cart, toasts, validateurs et toggles
```
