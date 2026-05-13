<?php require_once __DIR__ . '/../app/auth.php'; if (current_user()) redirect('dashboard.php'); ?>
<?php require_once __DIR__ . '/partials/header.php'; ?>
<section class="hero grid-2">
    <div class="hero-copy reveal">
        <p class="eyebrow">Data Integrity & Authentication Final Project</p>
        <h1>Secure Document Vault with animated 3D interface.</h1>
        <p class="hero-text">Upload, encrypt, sign, verify, and manage sensitive files through a full secure web UI powered by PHP and MySQL on XAMPP.</p>
        <div class="hero-actions">
            <a class="btn primary" href="register.php">Create Account</a>
            <a class="btn secondary" href="login.php">Login</a>
        </div>
    </div>
    <div class="vault-scene reveal delay-1" data-tilt-max="12">
        <div class="vault-core">
            <div class="vault-door"></div>
            <div class="vault-ring ring-1"></div>
            <div class="vault-ring ring-2"></div>
            <div class="vault-lock">🔐</div>
        </div>
        <div class="floating-card card-a glass">AES-256</div>
        <div class="floating-card card-b glass">JWT</div>
        <div class="floating-card card-c glass">RSA</div>
    </div>
</section>
<section class="feature-grid">
    <?php foreach ([
        ['Password Security', 'bcrypt hashing, policy validation, and secure sessions.'],
        ['Access Control', 'Admin, Manager, and User permissions using RBAC.'],
        ['Integrity', 'SHA-256 hashes and RSA digital signatures for every document.'],
        ['Secure Storage', 'Files are encrypted before storage using AES-256-GCM.'],
    ] as $feature): ?>
        <article class="feature-card glass tilt-card reveal">
            <h3><?= e($feature[0]) ?></h3>
            <p><?= e($feature[1]) ?></p>
        </article>
    <?php endforeach; ?>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
