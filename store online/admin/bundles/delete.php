<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $b = mysqli_fetch_assoc(mysqli_query($conn, "SELECT main_image FROM bundles WHERE id = $id"));
    if ($b && $b['main_image']) deleteImage($b['main_image']);
    mysqli_query($conn, "DELETE FROM bundles WHERE id = $id");
    flash('success', 'Bundle eliminato.');
}
header('Location: ' . SITE_URL . '/admin/bundles/');
exit;
?>