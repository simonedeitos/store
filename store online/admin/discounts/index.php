<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$error = '';
$success = '';

// Elimina regola
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM quantity_discounts WHERE id = $delId");
    flash('success', 'Regola sconto eliminata.');
    header('Location: ' . SITE_URL . '/admin/discounts/');
    exit;
}

// Crea nuova regola
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $softwareId = (int)($_POST['software_id'] ?? 0);
    $minQty = (int)($_POST['min_qty'] ?? 2);
    $discountPercent = (float)($_POST['discount_percent'] ?? 0);

    if ($softwareId <= 0 || $minQty < 2 || $discountPercent <= 0) {
        $error = 'Compila tutti i campi correttamente. Quantità minima = 2.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO quantity_discounts (software_id, min_qty, discount_percent) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iid', $softwareId, $minQty, $discountPercent);
        if (mysqli_stmt_execute($stmt)) {
            flash('success', 'Regola sconto aggiunta!');
            header('Location: ' . SITE_URL . '/admin/discounts/');
            exit;
        } else {
            $error = 'Errore durante il salvataggio.';
        }
    }
}

$allSoftware = mysqli_query($conn, "SELECT id, name FROM software ORDER BY name ASC");
$discountsR = mysqli_query($conn, "SELECT qd.*, s.name as software_name FROM quantity_discounts qd JOIN software s ON s.id = qd.software_id ORDER BY s.name ASC, qd.min_qty ASC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sconti Quantità - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4">Sconti Quantità</h1>
        
        <?php $msg = flash('success'); if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Come funziona:</strong> Quando un cliente mette nel carrello più copie dello stesso software, 
            la prima copia è a prezzo pieno, le successive avranno la percentuale di sconto indicata.
            <br>Puoi creare più regole per lo stesso software con quantità diverse (es: da 2 copie = 10%, da 5 copie = 20%).
        </div>

        <div class="row">
            <!-- FORM NUOVA REGOLA -->
            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Nuova Regola</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Software *</label>
                                <select name="software_id" class="form-select" required>
                                    <option value="">Seleziona...</option>
                                    <?php while ($s = mysqli_fetch_assoc($allSoftware)): ?>
                                        <option value="<?= $s['id'] ?>"><?= h($s['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quantità minima nel carrello *</label>
                                <input type="number" name="min_qty" class="form-control" min="2" required value="2">
                                <small class="text-muted">Da quante copie in poi si applica lo sconto</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sconto % sulle copie aggiuntive *</label>
                                <input type="number" name="discount_percent" class="form-control" step="0.01" min="0.01" max="100" required>
                                <small class="text-muted">La prima copia resta a prezzo pieno</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-2"></i>Aggiungi Regola</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- LISTA REGOLE -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Regole Attive</h5>
                        <?php if (mysqli_num_rows($discountsR) === 0): ?>
                            <p class="text-muted">Nessuna regola definita.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-admin">
                                        <tr><th>Software</th><th>Da Qtà</th><th>Sconto %</th><th></th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($d = mysqli_fetch_assoc($discountsR)): ?>
                                            <tr>
                                                <td class="fw-bold"><?= h($d['software_name']) ?></td>
                                                <td>≥ <?= $d['min_qty'] ?> copie</td>
                                                <td><span class="badge bg-success"><?= $d['discount_percent'] ?>%</span></td>
                                                <td>
                                                    <a href="?delete=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminare questa regola?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>