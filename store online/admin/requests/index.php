<?php
/**
 * AirDirector Store - Admin: Richieste Modifica Profilo
 */
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();

// Gestisci azione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if ($reqId > 0) {
        $req = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM profile_requests WHERE id = $reqId"));
        
        if ($req && $action === 'approve') {
            // Applica la modifica
            $field = dbEsc($req['field_name']);
            $newVal = dbEsc($req['new_value']);
            $userId = (int)$req['user_id'];
            
            $allowedFields = ['first_name', 'last_name', 'company', 'billing_address', 'vat_id'];
            if (in_array($req['field_name'], $allowedFields)) {
                mysqli_query($conn, "UPDATE users SET `$field` = '$newVal' WHERE id = $userId");
            }
            
            $notesEsc = dbEsc($adminNotes);
            mysqli_query($conn, "UPDATE profile_requests SET status = 'approved', admin_notes = '$notesEsc', resolved_at = NOW() WHERE id = $reqId");
            
            // Invia email conferma al cliente
            $userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $userId"));
            sendProfileChangeApprovedEmail($userData, $req['field_name'], $req['old_value'], $req['new_value']);
            
            flash('success', 'Richiesta approvata e dati aggiornati. Email inviata al cliente.');
            
        } elseif ($req && $action === 'reject') {
            $notesEsc = dbEsc($adminNotes);
            $userId = (int)$req['user_id'];
            mysqli_query($conn, "UPDATE profile_requests SET status = 'rejected', admin_notes = '$notesEsc', resolved_at = NOW() WHERE id = $reqId");
            
            // Invia email rifiuto al cliente
            $userData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $userId"));
            sendProfileChangeRejectedEmail($userData, $req['field_name'], $req['old_value'], $req['new_value'], $adminNotes);
            
            flash('success', 'Richiesta rifiutata. Email inviata al cliente.');
        }
        
        header('Location: ' . SITE_URL . '/admin/requests/');
        exit;
    }
}

$statusFilter = $_GET['status'] ?? 'pending';
$where = "WHERE pr.status = '" . dbEsc($statusFilter) . "'";
if ($statusFilter === 'all') $where = '';

$requestsR = mysqli_query($conn, "
    SELECT pr.*, u.first_name, u.last_name, u.email 
    FROM profile_requests pr 
    JOIN users u ON u.id = pr.user_id 
    $where
    ORDER BY pr.created_at DESC
");

$fieldLabels = [
    'first_name' => 'Nome',
    'last_name' => 'Cognome',
    'company' => 'Ragione Sociale',
    'billing_address' => 'Indirizzo Fatturazione',
    'vat_id' => 'Partita IVA'
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richieste Modifica - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4">Richieste Modifica Dati</h1>
        
        <?php $msg = flash('success'); if ($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
        
        <div class="mb-3">
            <a href="?status=pending" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">In Attesa</a>
            <a href="?status=approved" class="btn btn-sm <?= $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">Approvate</a>
            <a href="?status=rejected" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rifiutate</a>
            <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">Tutte</a>
        </div>

        <?php if (mysqli_num_rows($requestsR) === 0): ?>
            <div class="text-center py-5">
                <i class="bi bi-envelope-open text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">Nessuna richiesta</h5>
            </div>
        <?php else: ?>
            <?php while ($r = mysqli_fetch_assoc($requestsR)): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="fw-bold mb-1">
                                    <?= h($r['first_name'] . ' ' . $r['last_name']) ?> 
                                    <small class="text-muted">(<?= h($r['email']) ?>)</small>
                                </h6>
                                <p class="mb-1">
                                    Vuole modificare: <strong><?= h($fieldLabels[$r['field_name']] ?? $r['field_name']) ?></strong>
                                </p>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <small class="text-muted">Valore attuale:</small><br>
                                        <span class="text-danger"><?= h($r['old_value']) ?: '<em>vuoto</em>' ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Nuovo valore richiesto:</small><br>
                                        <span class="text-success fw-bold"><?= h($r['new_value']) ?></span>
                                    </div>
                                </div>
                                <small class="text-muted">Richiesta: <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small>
                                <?php if ($r['admin_notes']): ?>
                                    <br><small class="text-info">Note admin: <?= h($r['admin_notes']) ?></small>
                                <?php endif; ?>
                                <?php if ($r['resolved_at']): ?>
                                    <br><small class="text-muted">Risolta: <?= date('d/m/Y H:i', strtotime($r['resolved_at'])) ?></small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <div class="mb-2">
                                            <input type="text" name="admin_notes" class="form-control form-control-sm" placeholder="Note (opzionale)" style="width:200px;">
                                        </div>
                                        <button name="action" value="approve" class="btn btn-success btn-sm me-1" onclick="return confirm('Approvare e applicare la modifica? Il cliente riceverà email.')">
                                            <i class="bi bi-check-lg"></i> Approva
                                        </button>
                                        <button name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Rifiutare la richiesta? Il cliente riceverà email.')">
                                            <i class="bi bi-x-lg"></i> Rifiuta
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>