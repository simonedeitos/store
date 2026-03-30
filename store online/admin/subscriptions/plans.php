<?php
/**
 * AirDirector Store Admin - Gestione Piani Sottoscrizione
 */
require_once __DIR__ . '/../../functions.php';
requireAdmin();

$conn = getDBConnection();
$message = '';
$error = '';

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $name   = dbEsc(trim($_POST['name'] ?? ''));
        $desc   = dbEsc(trim($_POST['description'] ?? ''));
        $cycle  = in_array($_POST['billing_cycle'] ?? '', ['monthly','semiannual','annual']) ? $_POST['billing_cycle'] : 'monthly';
        $price  = floatval($_POST['price'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        $sort   = intval($_POST['sort_order'] ?? 0);

        if (!$name || $price <= 0) {
            $error = 'Nome e prezzo sono obbligatori.';
        } else {
            mysqli_query($conn, "INSERT INTO client_subscription_plans (name, description, billing_cycle, price, is_active, sort_order) VALUES ('$name','$desc','$cycle',$price,$active,$sort)");
            $message = 'Piano creato con successo.';
        }
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['plan_id'] ?? 0);
        mysqli_query($conn, "UPDATE client_subscription_plans SET is_active = 1 - is_active WHERE id = $id");
        $message = 'Piano aggiornato.';
    } elseif ($action === 'delete') {
        $id = (int)($_POST['plan_id'] ?? 0);
        $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM client_subscriptions WHERE plan_id = $id");
        $cnt = mysqli_fetch_assoc($r)['c'];
        if ($cnt > 0) {
            $error = 'Non puoi eliminare un piano con sottoscrizioni attive.';
        } else {
            mysqli_query($conn, "DELETE FROM client_subscription_plans WHERE id = $id");
            $message = 'Piano eliminato.';
        }
    }
}

$plans = mysqli_query($conn, "SELECT * FROM client_subscription_plans ORDER BY sort_order, id");

$billingLabels = ['monthly' => 'Mensile', 'semiannual' => 'Semestrale', 'annual' => 'Annuale'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piani Sottoscrizione - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold mb-0"><i class="bi bi-list-check me-2"></i>Piani Sottoscrizione</h1>
            <a href="<?= SITE_URL ?>/admin/subscriptions/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Indietro
            </a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <!-- Add Plan Form -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Aggiungi Piano</h5></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nome Piano *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ciclo *</label>
                            <select name="billing_cycle" class="form-select" required>
                                <option value="monthly">Mensile</option>
                                <option value="semiannual">Semestrale</option>
                                <option value="annual">Annuale</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Prezzo (€) *</label>
                            <input type="number" name="price" step="0.01" min="0.01" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ordine</label>
                            <input type="number" name="sort_order" value="0" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check me-3">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                                <label for="is_active" class="form-check-label">Attivo</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Aggiungi</button>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrizione</label>
                            <input type="text" name="description" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Plans List -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>#</th><th>Nome</th><th>Ciclo</th><th>Prezzo</th><th>Stato</th><th>Azioni</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($p = mysqli_fetch_assoc($plans)): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= h($p['name']) ?></div>
                                <?php if ($p['description']): ?><small class="text-muted"><?= h($p['description']) ?></small><?php endif; ?>
                            </td>
                            <td><?= $billingLabels[$p['billing_cycle']] ?? $p['billing_cycle'] ?></td>
                            <td><?= formatPrice($p['price']) ?></td>
                            <td>
                                <span class="badge bg-<?= $p['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $p['is_active'] ? 'Attivo' : 'Disattivo' ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning"><?= $p['is_active'] ? 'Disattiva' : 'Attiva' ?></button>
                                </form>
                                <form method="post" class="d-inline ms-1">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare questo piano?')">Elimina</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
