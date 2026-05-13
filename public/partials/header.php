<?php
require_once __DIR__ . '/../../app/auth.php';
require_once __DIR__ . '/../../app/csrf.php';
$user = current_user();
$active = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<canvas id="particle-canvas" aria-hidden="true"></canvas>
<div class="orb orb-a"></div>
<div class="orb orb-b"></div>
<div class="orb orb-c"></div>
<header class="topbar glass">
    <a class="brand" href="<?= app_url($user ? 'dashboard.php' : 'index.php') ?>">
        <span class="brand-cube">◆</span>
        <span>Secure Vault</span>
    </a>
    <button class="nav-toggle" data-nav-toggle aria-label="Toggle navigation">☰</button>
    <nav class="nav" data-nav>
        <?php if ($user): ?>
            <a class="nav-link <?= $active === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
            <a class="nav-link <?= $active === 'upload.php' ? 'active' : '' ?>" href="upload.php">Upload</a>
            <a class="nav-link <?= $active === 'verify.php' ? 'active' : '' ?>" href="verify.php">Verify</a>
            <?php if (in_array($user['role'], ['admin', 'manager'], true)): ?>
                <a class="nav-link <?= $active === 'manager.php' ? 'active' : '' ?>" href="manager.php">Review</a>
            <?php endif; ?>
            <?php if ($user['role'] === 'admin'): ?>
                <a class="nav-link <?= $active === 'admin.php' ? 'active' : '' ?>" href="admin.php">Admin</a>
            <?php endif; ?>
            <a class="nav-link <?= $active === 'profile.php' ? 'active' : '' ?>" href="profile.php">Profile</a>
            <a class="nav-link danger" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="nav-link <?= $active === 'login.php' ? 'active' : '' ?>" href="login.php">Login</a>
            <a class="nav-link <?= $active === 'register.php' ? 'active' : '' ?>" href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page-shell">
    <?php foreach (get_flash() as $flash): ?>
        <div class="flash <?= e($flash['type']) ?> glass tilt-card"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
