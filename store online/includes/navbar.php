<?php $cartCount = getCartCount(); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
            <i class="bi bi-airplane-engines me-2"></i><?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>">Catalogo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/#bundles">Bundle</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= SITE_URL ?>/cart.php">
                        <i class="bi bi-cart3 fs-5"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $cartCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>Il mio account
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/account/">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/account/orders.php">Ordini</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/account/licenses.php">Licenze</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/account/subscriptions.php"><i class="bi bi-broadcast me-1"></i>AirDirector Client</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/account/profile.php">Profilo</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Accedi</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>