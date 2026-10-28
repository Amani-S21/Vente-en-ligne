<?php
/**
 * Initialisation automatique du schéma et des données de démonstration
 */

function initializeDatabase(PDO $pdo): void {
    // 1. Création des tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name VARCHAR(120) NOT NULL,
            email VARCHAR(120) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'customer',
            phone VARCHAR(30) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city VARCHAR(80) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(80) NOT NULL,
            slug VARCHAR(80) UNIQUE NOT NULL,
            icon VARCHAR(50) DEFAULT 'bi-tag',
            description TEXT DEFAULT NULL,
            image_url TEXT DEFAULT NULL
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            name VARCHAR(150) NOT NULL,
            slug VARCHAR(160) UNIQUE NOT NULL,
            short_desc VARCHAR(255) DEFAULT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            old_price DECIMAL(10, 2) DEFAULT NULL,
            stock INTEGER DEFAULT 10,
            rating DECIMAL(3, 1) DEFAULT 4.5,
            reviews_count INTEGER DEFAULT 0,
            image_url TEXT NOT NULL,
            is_featured INTEGER DEFAULT 0,
            is_new INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT NULL,
            order_number VARCHAR(30) UNIQUE NOT NULL,
            tracking_code VARCHAR(30) UNIQUE NOT NULL,
            total_amount DECIMAL(10, 2) NOT NULL,
            shipping_fee DECIMAL(10, 2) DEFAULT 0.00,
            tax_amount DECIMAL(10, 2) DEFAULT 0.00,
            status VARCHAR(30) DEFAULT 'pending',
            shipping_name VARCHAR(120) NOT NULL,
            shipping_email VARCHAR(120) NOT NULL,
            shipping_phone VARCHAR(30) NOT NULL,
            shipping_address TEXT NOT NULL,
            shipping_city VARCHAR(80) NOT NULL,
            payment_method VARCHAR(40) NOT NULL,
            payment_status VARCHAR(30) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER DEFAULT NULL,
            product_name VARCHAR(150) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            quantity INTEGER NOT NULL DEFAULT 1,
            total DECIMAL(10, 2) NOT NULL,
            image_url TEXT DEFAULT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            user_name VARCHAR(100) NOT NULL,
            rating INTEGER NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        );
    ");

    // 2. Insérer utilisateurs par défaut
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $userPass = password_hash('password123', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone, address, city) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Administrateur Pro', 'admin@boutique.com', $adminPass, 'admin', '+243 810 000 001', '12 Avenue de la Paix, Gombe', 'Kinshasa']);
    $stmt->execute(['Jean-Luc Mukendi', 'client@boutique.com', $userPass, 'customer', '+243 820 000 002', '45 Boulevard du 30 Juin', 'Kinshasa']);

    // 3. Insérer catégories
    $categories = [
        [
            'Électronique & High-Tech', 'electronique', 'bi-laptop',
            'Smartphones dernier cri, ordinateurs ultra-performants et accessoires technologiques.',
            'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=600&q=80'
        ],
        [
            'Mode & Habillement', 'mode', 'bi-bag',
            'Collections tendance de vêtements élégants, chaussures de créateurs et style urbain.',
            'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=600&q=80'
        ],
        [
            'Audio & Multimédia', 'audio', 'bi-headphones',
            'Casques audio haute fidélité, écouteurs sans fil et enceintes Bluetooth puissantes.',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80'
        ],
        [
            'Montres & Accessoires', 'accessoires', 'bi-watch',
            'Montres connectées élégantes, lunettes de soleil de luxe et maroquinerie fine.',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80'
        ],
        [
            'Maison & Bureau', 'maison', 'bi-lamp',
            'Design d\'intérieur moderne, lampes connectées et accessoires ergonomiques de bureau.',
            'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=600&q=80'
        ]
    ];

    $catStmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, image_url) VALUES (?, ?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }

    // 4. Insérer produits réalistes
    $products = [
        [
            1, // Électronique
            'MacBook Pro 16" M3 Max 32Go',
            'macbook-pro-16-m3-max',
            'Puissance phénoménale pour les créateurs et développeurs avec puce M3 Max.',
            'Le MacBook Pro 16 pouces avec puce M3 Max repousse les limites des performances pour les professionnels. Doté d\'un superbe écran Liquid Retina XDR de 16,2 pouces avec technologie ProMotion 120Hz, 32 Go de mémoire unifiée et 1 To de stockage SSD ultra-rapide. Autonomie record allant jusqu\'à 22 heures, système audio à 6 haut-parleurs avec Audio Spatial et connectivité complète Thunderbolt 4, HDMI et lecteur de carte SDXC.',
            2499.00, 2799.00, 8, 4.9, 48,
            'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=800&q=80',
            1, 1
        ],
        [
            1, // Électronique
            'Smartphone iPhone 15 Pro Titanium 256Go',
            'iphone-15-pro-titanium',
            'Design en titane aérospatial, puce A17 Pro et capteur principal 48 Mpx.',
            'Découvrez l\'iPhone 15 Pro taillé dans un titane de qualité aérospatiale d\'une légèreté et d\'une robustesse remarquables. Écran Super Retina XDR Always-On avec Dynamic Island, bouton Action personnalisable, connecteur USB-C haute vitesse et zoom optique 3x exceptionnel. Idéal pour la photographie pro et le multitâche exigeant.',
            1199.00, 1349.00, 15, 4.8, 86,
            'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=800&q=80',
            1, 0
        ],
        [
            3, // Audio
            'Casque Sony WH-1000XM5 Réduction Active',
            'casque-sony-wh-1000xm5',
            'La meilleure réduction de bruit active du marché avec son haute résolution certifié.',
            'Le casque circum-aural Sony WH-1000XM5 redéfinit l\'écoute sans distraction grâce à son double processeur de réduction active du bruit et ses 8 micros. Profitez d\'un confort absolu avec un arceau ultra-léger, jusqu\'à 30 heures d\'autonomie continue avec recharge ultra-rapide (3h d\'écoute en 3 min de charge) et la compatibilité LDAC pour une fidélité sonore sans compromis.',
            349.00, 399.00, 24, 4.9, 134,
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
            1, 1
        ],
        [
            4, // Montres & Accessoires
            'Montre Connectée Galaxy Watch Ultra 47mm',
            'galaxy-watch-ultra-47mm',
            'Robuste en titane militaire, GPS double fréquence et autonomie multi-jours.',
            'Conçue pour repousser vos limites dans les environnements les plus extrêmes. Écran saphir ultra-résistant de 3000 nits, capteur BioActive dernière génération pour le suivi précis de votre santé, résistance à l\'eau 10 ATM et bouton d\'accès rapide pour les activités sportives d\'endurance.',
            499.00, 579.00, 12, 4.7, 39,
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
            1, 0
        ],
        [
            2, // Mode
            'Sneakers Urbaines Limited Edition Horizon',
            'sneakers-urbaines-horizon',
            'Confort respirant, semelle amortissante Air-Glide et style contemporain.',
            'Alliant style streetwear haut de gamme et technologie de semelle ergonomique, ces sneakers vous accompagnent au quotidien avec un confort sans égal. Tige en cuir nubuck respirant et mesh technique, semelle extérieure antidérapante en caoutchouc recyclé et finitions haut de gamme.',
            139.00, 179.00, 30, 4.6, 52,
            'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
            1, 1
        ],
        [
            2, // Mode
            'Veste en Cuir Véritable Vintage Biker',
            'veste-cuir-vintage-biker',
            'Cuir d\'agneau véritable souple avec doublure thermique et fermetures zippées YKK.',
            'Une pièce intemporelle pour toute garde-robe masculine et féminine. Cuir pleine fleur sélectionné à la main, patine naturelle résistante au temps, coutures renforcées et poches intérieures sécurisées pour smartphone et portefeuille.',
            289.00, 349.00, 7, 4.8, 29,
            'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=80',
            0, 0
        ],
        [
            3, // Audio
            'Écouteurs True Wireless AirPods Pro 2',
            'airpods-pro-2-usb-c',
            'Réduction active du bruit 2x plus efficace et audio spatial personnalisé.',
            'Dotés de la puce H2 révolutionnaire, les AirPods Pro 2 offrent une clarté sonore exceptionnelle, une transparence adaptative intelligente pour rester connecté à votre environnement et un boîtier de charge MagSafe résistant aux éclaboussures avec haut-parleur intégré.',
            229.00, 279.00, 40, 4.9, 210,
            'https://images.unsplash.com/photo-1600294037681-c80b4cb5b434?auto=format&fit=crop&w=800&q=80',
            1, 0
        ],
        [
            5, // Maison
            'Lampe de Bureau Ergonomique Smart LED',
            'lampe-bureau-smart-led',
            'Éclairage anti-reflet réglable, variateur tactile et port de charge sans fil Qi.',
            'Optimisez votre concentration et protégez vos yeux avec cette lampe de bureau design. Température de couleur ajustable de 2700K à 6500K, minuterie d\'arrêt automatique, base en aluminium brossé et socle avec charge sans fil 15W intégré pour recharger votre téléphone sans fil.',
            79.00, 99.00, 25, 4.5, 31,
            'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',
            0, 1
        ],
        [
            4, // Accessoires
            'Sac à Dos Voyage & Tech Imperméable 30L',
            'sac-a-dos-voyage-tech-30l',
            'Compartiment ordinateur 17", port de charge USB externe et cadenas antivol TSA.',
            'Le compagnon idéal pour vos trajets quotidiens et vos voyages internationaux. Tissu Oxford déperlant résistant aux déchirures, bretelles rembourrées ergonomiques pour soulager les épaules et multiples poches secrètes pour passeport et accessoires précieux.',
            89.00, 119.00, 18, 4.7, 44,
            'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
            0, 0
        ],
        [
            1, // Électronique
            'Appareil Photo Mirrorless Sony Alpha A7 IV',
            'sony-alpha-a7-iv-boitier-nu',
            'Capteur plein format 33 Mpx, enregistrement vidéo 4K 60p et autofocus IA en temps réel.',
            'L\'appareil hybride de référence pour photographes et vidéastes professionnels. Mise au point automatique ultra-rapide sur les yeux humains, animaux et oiseaux, stabilisation d\'image 5 axes intégrée au boîtier et double lecteur de carte SD/CFexpress Type A.',
            2199.00, 2399.00, 5, 4.9, 19,
            'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',
            1, 0
        ],
        [
            5, // Maison
            'Enceinte d\'Ambiance Bois & Son 360°',
            'enceinte-bois-son-360',
            'Finition en chêne naturel véritable avec acoustique pure et connexion Bluetooth 5.3.',
            'Un objet d\'art acoustique pour votre salon. Conçue avec des matériaux nobles et écoresponsables, elle diffuse un son chaleureux et équilibré dans toute la pièce avec des basses profondes et des aigus cristallins.',
            169.00, 199.00, 14, 4.6, 23,
            'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
            0, 1
        ],
        [
            2, // Mode
            'Lunettes de Soleil Polarisées Aviator Classic',
            'lunettes-soleil-aviator-polarisees',
            'Verres polarisés UV400 haute protection avec monture ultra-légère en acier.',
            'Style emblématique garanti. Protection à 100% contre les rayons UV dangereux, réduction maximale des reflets éblouissants sur l\'eau et la route, livrées avec étui rigide en cuir et chiffonnette en microfibre.',
            65.00, 85.00, 35, 4.7, 67,
            'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=800&q=80',
            0, 0
        ]
    ];

    $prodStmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, short_desc, description, price, old_price, stock, rating, reviews_count, image_url, is_featured, is_new) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $prodStmt->execute($p);
    }

    // 5. Insérer des commandes de démonstration pour tester le suivi immédiatement
    $orders = [
        [
            2, // Client Jean-Luc Mukendi
            'CMD-2026-001',
            'TRK-8921-CD',
            349.00, 10.00, 55.84,
            'shipped', // Statut: Expédiée
            'Jean-Luc Mukendi', 'client@boutique.com', '+243 820 000 002',
            '45 Boulevard du 30 Juin, Résidence Elysée', 'Kinshasa',
            'mobile_money', 'paid'
        ],
        [
            2,
            'CMD-2026-002',
            'TRK-4412-CD',
            139.00, 5.00, 22.24,
            'delivered', // Statut: Livrée
            'Jean-Luc Mukendi', 'client@boutique.com', '+243 820 000 002',
            '45 Boulevard du 30 Juin, Résidence Elysée', 'Kinshasa',
            'card', 'paid'
        ]
    ];

    $ordStmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, tracking_code, total_amount, shipping_fee, tax_amount, status, shipping_name, shipping_email, shipping_phone, shipping_address, shipping_city, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");

    foreach ($orders as $idx => $ord) {
        $ordStmt->execute($ord);
        $orderId = $pdo->lastInsertId();

        if ($idx === 0) {
            $itemStmt->execute([$orderId, 3, 'Casque Sony WH-1000XM5 Réduction Active', 349.00, 1, 349.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80']);
        } else {
            $itemStmt->execute([$orderId, 5, 'Sneakers Urbaines Limited Edition Horizon', 139.00, 1, 139.00, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80']);
        }
    }

    // 6. Insérer avis clients
    $reviews = [
        [1, 'Marc Antoine', 5, 'Performance stratosphérique avec la puce M3 Max ! Le rendu vidéo 4K est instantané. Je recommande à 100%.'],
        [1, 'Sophie Dubois', 5, 'Superbe écran et autonomie bluffante, vaut largement son investissement.'],
        [3, 'Alain Kasongo', 5, 'La réduction de bruit est la meilleure que j\'ai jamais testée dans l\'avion et au bureau.'],
        [3, 'Nathalie L.', 5, 'Son limpide, très confortable même après 6 heures d\'écoute consécutive.'],
        [5, 'David M.', 4, 'Très belles sneakers, légères et confortables. Reçues en 48h !']
    ];

    $revStmt = $pdo->prepare("INSERT INTO reviews (product_id, user_name, rating, comment) VALUES (?, ?, ?, ?)");
    foreach ($reviews as $rev) {
        $revStmt->execute($rev);
    }
}
