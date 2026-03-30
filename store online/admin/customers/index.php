<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$search = trim($_GET['search'] ?? '');
$where = '';
if ($search) { $s = dbEsc($search); $where = "WHERE email LIKE '%$s%' OR first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR company LIKE '%$s%'"; }
$usersR = mysqli_query($conn, "SELECT * FROM users $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clienti - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include __DIR__ . '/../../includes/admin_sidebar.php'; ?>
    <div class="admin-content">
        <h1 class="fw-bold mb-4">Clienti</h1>
        <form class="mb-3 d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Cerca nome, email, azienda..." value="<?= h($search) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-admin">
                        <tr><th>Nome</th><th>Email</th><th>Azienda</th><th>P.IVA</th><th>Registrato</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($usersR)): ?>
                            <tr>
                                <td class="fw-bold"><?= h($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                <td><?= h($u['email']) ?></td>
                                <td class="small"><?= h($u['company']) ?: '-' ?></td>
                                <td class="small"><?= h($u['vat_id']) ?: '-' ?></td>
                                <td class="small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                <td><a href="view.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">Dettagli</a></td>
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