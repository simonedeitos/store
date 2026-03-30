<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function adminActive($dir, $page = '') {
    global $currentDir, $currentPage;
    if ($page && $currentPage === $page && $currentDir === $dir) return 'active';
    if (!$page && $currentDir === $dir) return 'active';
    return '';
}
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white admin-sidebar" style="width: 260px; min-height: 100vh;">
    <a href="<?= SITE_URL ?>/admin/" class="d-flex align-items-center mb-3 text-white text-decoration-none">
        <i class="bi bi-gear-wide-connected fs-4 me-2"></i>
        <span class="fs-5 fw-semibold">Admin Panel</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?= SITE_URL ?>/admin/" class="nav-link text-white <?= $currentPage === 'index.php' && $currentDir === 'admin' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/software/" class="nav-link text-white <?= adminActive('software') ?>">
                <i class="bi bi-box-seam me-2"></i>Software
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/bundles/" class="nav-link text-white <?= adminActive('bundles') ?>">
                <i class="bi bi-collection me-2"></i>Bundle
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/orders/" class="nav-link text-white <?= adminActive('orders') ?>">
                <i class="bi bi-receipt me-2"></i>Ordini
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/licenses/" class="nav-link text-white <?= adminActive('licenses') ?>">
                <i class="bi bi-key me-2"></i>Licenze
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/customers/" class="nav-link text-white <?= adminActive('customers') ?>">
                <i class="bi bi-people me-2"></i>Clienti
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/coupons/" class="nav-link text-white <?= adminActive('coupons') ?>">
                <i class="bi bi-tag me-2"></i>Coupon
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/discounts/" class="nav-link text-white <?= adminActive('discounts') ?>">
                <i class="bi bi-percent me-2"></i>Sconti Quantità
            </a>
        </li>
        <li class="mt-3">
            <small class="text-muted text-uppercase px-3" style="font-size:0.7rem;letter-spacing:1px;">AirDirector Client</small>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/subscriptions/" class="nav-link text-white <?= adminActive('subscriptions') ?>">
                <i class="bi bi-broadcast me-2"></i>Sottoscrizioni Client
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/requests/" class="nav-link text-white <?= adminActive('requests') ?>">
                <i class="bi bi-envelope me-2"></i>Richieste
            </a>
        </li>

        <li class="mt-3">
            <small class="text-muted text-uppercase px-3" style="font-size:0.7rem;letter-spacing:1px;">Email & Notifiche</small>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/email/settings.php" class="nav-link text-white <?= adminActive('email', 'settings.php') ?>">
                <i class="bi bi-envelope-at me-2"></i>Email Settings
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/email/templates.php" class="nav-link text-white <?= adminActive('email', 'templates.php') ?>">
                <i class="bi bi-file-earmark-code me-2"></i>Email Templates
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/email/notifications.php" class="nav-link text-white <?= adminActive('email', 'notifications.php') ?>">
                <i class="bi bi-bell me-2"></i>Notifiche Admin
            </a>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/email/log.php" class="nav-link text-white <?= adminActive('email', 'log.php') ?>">
                <i class="bi bi-clock-history me-2"></i>Log Email
            </a>
        </li>

        <li class="mt-3">
            <small class="text-muted text-uppercase px-3" style="font-size:0.7rem;letter-spacing:1px;">Sistema</small>
        </li>
        <li>
            <a href="<?= SITE_URL ?>/admin/settings/" class="nav-link text-white <?= adminActive('settings') ?>">
                <i class="bi bi-sliders me-2"></i>Impostazioni
            </a>
        </li>
    </ul>
    <hr>
    <div class="d-flex align-items-center">
        <i class="bi bi-shield-lock me-2"></i>
        <span class="me-auto"><?= h(getCurrentAdmin()['username'] ?? 'Admin') ?></span>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="text-white" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</div>