<?php

declare(strict_types=1);

$pageScripts = $pageScripts ?? [];
?>
        </main>
        <footer class="site-footer">
            <div class="footer-grid">
                <div>
                    <h3><?= e(app_config('app_name')) ?></h3>
                    <p>Master new skills with crystal-clear lessons and real-time insights tailored for your success.</p>
                </div>
                <div>
                    <h4>Explore</h4>
                    <a href="<?= e(site_url('index.php')) ?>">Courses</a>
                    <a href="<?= e(site_url('about.php')) ?>">About Us</a>
                    <a href="<?= e(site_url('contact.php')) ?>">Contact Us</a>
                </div>
                <div>
                    <h4>Student</h4>
                    <a href="<?= e(site_url('dashboard.php')) ?>">Dashboard</a>
                    <a href="<?= e(site_url('profile.php')) ?>">Profile</a>
                    <a href="<?= e(site_url('login.php')) ?>">Login</a>
                </div>
                <div>
                    <h4>Social</h4>
                    <a href="https://www.linkedin.com" target="_blank" rel="noreferrer">LinkedIn</a>
                    <a href="https://www.instagram.com" target="_blank" rel="noreferrer">Instagram</a>
                    <a href="https://www.youtube.com" target="_blank" rel="noreferrer">YouTube</a>
                </div>
            </div>
            <p class="footer-note">&copy; 2026 LearnQ | Managed by BIPE | Designed by Computer Science Batch [ 2025-26 ]</p>
        </footer>
    </div>
    <script src="<?= e(site_url('assets/js/global.js')) ?>"></script>
    <?php foreach ($pageScripts as $script): ?>
        <script src="<?= e(site_url($script)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
