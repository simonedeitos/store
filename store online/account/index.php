<?php
/**
 * AirDirector Store - Dashboard Account
 */
require_once __DIR__ . '/../functions.php';
requireLogin();

$conn = getDBConnection();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser();

// Stats
$ordersR = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM orders WHERE user_id = $userId");
$totalOrders = mysqli_fetch_assoc($ordersR)['cnt'];

$licensesR = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM licenses WHERE user_id = $userId");
$totalLicenses = mysqli_fetch_assoc($licensesR)['cnt'];

$activeR = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM licenses WHERE user_id = $userId AND is_active = 1");
$activeLicenses = mysqli_fetch_assoc($activeR)['cnt'];

// Ultimi ordini
$recentOrders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC LIMIT 5");

define('PAGE_TITLE', 'Il mio Account');
include __DIR__ . '/../includes/header.php';
?>

<script>window.SITE_URL = '<?= SITE_URL ?>';</script>

<div class="container py-4">
    <h1 class="section-title">Benvenuto, <?= h($user['first_name']) ?>!</h1>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?= $totalOrders ?></div>
                <div class="stat-label">Ordini</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?= $totalLicenses ?></div>
                <div class="stat-label">Licenze Totali</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?= $activeLicenses ?></div>
                <div class="stat-label">Licenze Attive</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-3">
            <a href="<?= SITE_URL ?>/account/orders.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="bi bi-receipt fs-1 text-primary"></i>
                    <h6 class="mt-2 fw-bold">I miei Ordini</h6>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= SITE_URL ?>/account/licenses.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="bi bi-key fs-1 text-success"></i>
                    <h6 class="mt-2 fw-bold">Le mie Licenze</h6>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= SITE_URL ?>/account/download.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="bi bi-download fs-1 text-info"></i>
                    <h6 class="mt-2 fw-bold">Download</h6>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= SITE_URL ?>/account/profile.php" class="card text-decoration-none h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-gear fs-1 text-warning"></i>
                    <h6 class="mt-2 fw-bold">Profilo</h6>
                </div>
            </a>
        </div>
    </div>

    <!-- Ultimi Ordini -->
    <?php if (mysqli_num_rows($recentOrders) > 0): ?>
        <h4 class="fw-bold mt-5 mb-3">Ultimi Ordini</h4>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>#</th><th>Data</th><th>Totale</th><th>Stato</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php while ($o = mysqli_fetch_assoc($recentOrders)): ?>
                            <tr>
                                <td class="fw-bold"><?= $o['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td><?= formatPrice($o['total']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= SITE_URL ?>/account/orders.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Dettagli</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>