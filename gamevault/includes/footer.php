<?php
// includes/footer.php
?>
</main>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-logo">
            <span class="logo-icon">⚡</span> <?= SITE_NAME ?>
        </div>
        <p class="footer-tagline">Votre univers gaming, au même endroit.</p>
        <div class="footer-links">
            <a href="<?= BASE_URL ?>/index.php">Accueil</a>
            <a href="<?= BASE_URL ?>/games.php">Jeux</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/profile.php">Mon Profil</a>
            <?php endif; ?>
        </div>
        <p class="footer-copy">&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Projet PHP B2</p>
    </div>
</footer>

<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
