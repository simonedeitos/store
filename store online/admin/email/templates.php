<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$success = '';
$error = '';

// Modifica template
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $id = (int)$_POST['template_id'];
    $subject = trim($_POST['subject'] ?? '');
    $bodyHtml = $_POST['body_html'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $stmt = mysqli_prepare($conn, "UPDATE email_templates SET subject=?, body_html=?, is_active=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssii', $subject, $bodyHtml, $isActive, $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Template aggiornato!';
    } else {
        $error = 'Errore nel salvataggio.';
    }
}

$templatesR = mysqli_query($conn, "SELECT * FROM email_templates ORDER BY id ASC");
$templates = [];
while ($t = mysqli_fetch_assoc($templatesR)) $templates[] = $t;

// Template da modificare
$editId = (int)($_GET['edit'] ?? 0);
$editTemplate = null;
if ($editId) {
    foreach ($templates as $t) {
        if ($t['id'] == $editId) { $editTemplate = $t; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4"><i class="bi bi-file-earmark-code me-2"></i>Email Templates</h1>
        
        <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

        <?php if ($editTemplate): ?>
            <!-- EDITOR TEMPLATE -->
            <a href="<?= SITE_URL ?>/admin/email/templates.php" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left me-1"></i>Indietro</a>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-1"><?= h($editTemplate['name']) ?></h5>
                    <p class="text-muted small mb-3"><?= h($editTemplate['description']) ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="template_id" value="<?= $editTemplate['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Oggetto Email</label>
                            <input type="text" name="subject" class="form-control" value="<?= h($editTemplate['subject']) ?>">
                            <small class="text-muted">Puoi usare variabili come <code>{{first_name}}</code>, <code>{{order_id}}</code>, ecc.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Corpo Email (HTML)</label>
                            <textarea name="body_html" class="form-control font-monospace" rows="20" style="font-size:0.85rem;"><?= h($editTemplate['body_html']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Variabili Disponibili</label>
                            <div class="bg-light p-3 rounded">
                                <?php 
                                $vars = explode(', ', $editTemplate['available_variables'] ?? '');
                                foreach ($vars as $v): 
                                    $v = trim($v);
                                    if ($v):
                                ?>
                                    <code class="me-2 mb-1 d-inline-block"><?= h($v) ?></code>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?= $editTemplate['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Template attivo</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="save_template" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salva Template</button>
                            <button type="button" class="btn btn-outline-info" onclick="previewTemplate()"><i class="bi bi-eye me-1"></i>Anteprima</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ANTEPRIMA -->
            <div class="card d-none" id="previewCard">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Anteprima</h5>
                    <div id="previewFrame" style="background:#f1f5f9;padding:20px;border-radius:8px;"></div>
                </div>
            </div>

            <script>
            function previewTemplate() {
                const html = document.querySelector('textarea[name="body_html"]').value;
                // Sostituisci variabili con placeholder per l'anteprima
                let preview = html
                    .replace(/\{\{first_name\}\}/g, 'Mario')
                    .replace(/\{\{last_name\}\}/g, 'Rossi')
                    .replace(/\{\{email\}\}/g, 'mario@example.com')
                    .replace(/\{\{company\}\}/g, 'Azienda SRL')
                    .replace(/\{\{site_name\}\}/g, '<?= SITE_NAME ?>')
                    .replace(/\{\{site_url\}\}/g, '<?= SITE_URL ?>')
                    .replace(/\{\{year\}\}/g, '<?= date("Y") ?>')
                    .replace(/\{\{date\}\}/g, '<?= date("d/m/Y") ?>')
                    .replace(/\{\{time\}\}/g, '<?= date("H:i") ?>')
                    .replace(/\{\{order_id\}\}/g, '1234')
                    .replace(/\{\{order_total\}\}/g, '€ 299,00')
                    .replace(/\{\{order_items\}\}/g, '<p>• Software Example x1 - € 299,00</p>')
                    .replace(/\{\{serial_key\}\}/g, 'ADR-A8F3-K9B2-X1M7')
                    .replace(/\{\{software_name\}\}/g, 'AirDirector Pro')
                    .replace(/\{\{hardware_id\}\}/g, 'ABC123-DEF456')
                    .replace(/\{\{reset_link\}\}/g, '#')
                    .replace(/\{\{license_list\}\}/g, '<p>🔑 ADR-A8F3-K9B2-X1M7 - AirDirector Pro</p>')
                    .replace(/\{\{download_list\}\}/g, '<p><a href="#">📥 Scarica AirDirector Pro</a></p>')
                    .replace(/\{\{customer_name\}\}/g, 'Mario Rossi')
                    .replace(/\{\{customer_email\}\}/g, 'mario@example.com')
                    .replace(/\{\{customer_company\}\}/g, 'Azienda SRL')
                    .replace(/\{\{customer_vat\}\}/g, 'IT12345678901')
                    .replace(/\{\{field_name\}\}/g, 'Ragione Sociale')
                    .replace(/\{\{old_value\}\}/g, 'Vecchia SRL')
                    .replace(/\{\{new_value\}\}/g, 'Nuova SRL')
                    .replace(/\{\{admin_notes\}\}/g, '')
                    .replace(/\{\{#\w+\}\}/g, '').replace(/\{\{\/\w+\}\}/g, '');
                
                document.getElementById('previewFrame').innerHTML = preview;
                document.getElementById('previewCard').classList.remove('d-none');
                document.getElementById('previewCard').scrollIntoView({behavior: 'smooth'});
            }
            </script>

        <?php else: ?>
            <!-- LISTA TEMPLATES -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-admin">
                            <tr><th>Template</th><th>Chiave</th><th>Oggetto</th><th>Stato</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $t): ?>
                                <tr>
                                    <td>
                                        <strong><?= h($t['name']) ?></strong>
                                        <br><small class="text-muted"><?= h($t['description']) ?></small>
                                    </td>
                                    <td><code><?= h($t['template_key']) ?></code></td>
                                    <td class="small"><?= h($t['subject']) ?></td>
                                    <td>
                                        <span class="badge <?= $t['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $t['is_active'] ? 'Attivo' : 'Disattivo' ?></span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Modifica</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>