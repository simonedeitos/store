<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    mysqli_query($conn, "DELETE FROM coupons WHERE id = $id");
    flash('success', 'Coupon eliminato.');
}
header('Location: ' . SITE_URL . '/admin/coupons/');
exit;
?>