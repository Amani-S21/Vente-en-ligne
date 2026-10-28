    <!-- Pied de page officiel -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Colonne Marque -->
                <div class="footer-brand">
                    <div class="brand-logo" style="color: white; margin-bottom: 1rem;">
                        <div class="logo-icon">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <span>Nova<span style="color: #818cf8;">Store</span></span>
                    </div>
                    <p style="font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem;">
                        Plateforme e-commerce nouvelle génération offrant une expérience d'achat fluide, des articles certifiés authentiques et un service de livraison rapide et sécurisé.
                    </p>
                    <div style="display: flex; gap: 0.75rem; font-size: 1.25rem;">
                        <a href="#" style="color: #94a3b8;"><i class="bi bi-facebook"></i></a>
                        <a href="#" style="color: #94a3b8;"><i class="bi bi-instagram"></i></a>
                        <a href="#" style="color: #94a3b8;"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" style="color: #94a3b8;"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <!-- Colonne Liens Rapides -->
                <div class="footer-col">
                    <h4>Navigation</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="bi bi-chevron-right"></i> Page d'accueil</a></li>
                        <li><a href="products.php"><i class="bi bi-chevron-right"></i> Tous les produits</a></li>
                        <li><a href="about.html"><i class="bi bi-chevron-right"></i> À Propos</a></li>
                        <li><a href="contact.html"><i class="bi bi-chevron-right"></i> Contact & Support</a></li>
                        <li><a href="cart.php"><i class="bi bi-chevron-right"></i> Mon panier</a></li>
                        <li><a href="track.php"><i class="bi bi-chevron-right"></i> Suivi de commande</a></li>
                        <li><a href="profile.php"><i class="bi bi-chevron-right"></i> Mon compte client</a></li>
                    </ul>
                </div>

                <!-- Colonne Catégories -->
                <div class="footer-col">
                    <h4>Rayons Phares</h4>
                    <ul class="footer-links">
                        <li><a href="products.php?category=electronique"><i class="bi bi-laptop"></i> High-Tech & PC</a></li>
                        <li><a href="products.php?category=mode"><i class="bi bi-bag"></i> Mode & Vêtements</a></li>
                        <li><a href="products.php?category=audio"><i class="bi bi-headphones"></i> Audio & Casques</a></li>
                        <li><a href="products.php?category=accessoires"><i class="bi bi-watch"></i> Montres & Bijoux</a></li>
                        <li><a href="products.php?category=maison"><i class="bi bi-lamp"></i> Maison & Bureau</a></li>
                    </ul>
                </div>

                <!-- Colonne Newsletter & Paiements -->
                <div class="footer-col">
                    <h4>Restez informé</h4>
                    <p style="font-size: 0.875rem; margin-bottom: 1rem;">Recevez nos promotions exclusives et nouveautés directement dans votre boîte mail.</p>
                    
                    <form onsubmit="event.preventDefault(); showToast('Merci ! Vous êtes bien inscrit à la newsletter NovaStore.', 'success');" style="margin-bottom: 1.5rem;">
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="email" placeholder="Votre email..." required style="flex:1; padding: 0.65rem 0.85rem; border-radius: var(--radius-md); border: 1px solid #334155; background: #1e293b; color: white; font-size: 0.85rem; outline: none;">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send-fill"></i></button>
                        </div>
                    </form>

                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">Modes de paiement acceptés :</div>
                    <div style="display: flex; gap: 0.6rem; font-size: 1.5rem; color: #94a3b8; align-items: center;">
                        <i class="bi bi-credit-card-2-front" title="Carte Bancaire Visa / Mastercard"></i>
                        <i class="bi bi-phone" title="Mobile Money (M-Pesa, Orange Money, Airtel)"></i>
                        <i class="bi bi-cash-coin" title="Paiement à la livraison"></i>
                        <i class="bi bi-shield-lock" title="Paiement chiffré SSL 256-bit"></i>
                    </div>
                </div>
            </div>

            <!-- Bas de page -->
            <div class="footer-bottom">
                <div>
                    &copy; <?= date('Y') ?> <strong>NovaStore</strong>. Tous droits réservés. Projet Académique de Vente en Ligne.
                </div>
                <div style="color: #64748b;">
                    Conception : <strong>HTML5 &bull; CSS3 &bull; JavaScript &bull; PHP PDO &bull; MySQL / SQLite</strong>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
