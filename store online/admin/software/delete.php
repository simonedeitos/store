<?php
require_once __DIR__ . '/../../functions.php';
requireAdmin();
$conn = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    // Elimina immagini
    $sw = mysqli_fetch_assoc(mysqli_query($conn, "SELECT main_image FROM software WHERE id = $id"));
    if ($sw && $sw['main_image']) deleteImage($sw['main_image']);
    
    $imgs = mysqli_query($conn, "SELECT image_path FROM software_images WHERE software_id = $id");
    while ($img = mysqli_fetch_assoc($imgs)) deleteImage($img['image_path']);
    
    mysqli_query($conn, "DELETE FROM software WHERE id = $id");
    flash('success', 'Software eliminato.');
}
header('Location: ' . SITE_URL . '/admin/software/');
exit;
?>