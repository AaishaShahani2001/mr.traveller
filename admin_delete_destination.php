<?php
session_start();
require "config.php";

/* -------- Security: Admin only -------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* -------- Validate ID -------- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_manage_destinations.php");
    exit;
}

$id = (int)$_GET['id'];

/* -------- Fetch image name -------- */
$imgStmt = $conn->prepare("SELECT image FROM destinations WHERE dest_id = ?");
$imgStmt->execute([$id]);
$destination = $imgStmt->fetch(PDO::FETCH_ASSOC);

/* -------- Delete image file safely -------- */
if ($destination && !empty($destination['image'])) {
    $imagePath = "uploads/" . $destination['image'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

/* -------- Delete destination record -------- */
$delStmt = $conn->prepare("DELETE FROM destinations WHERE dest_id = ?");
$delStmt->execute([$id]);

/* -------- Redirect with success message -------- */
header("Location: admin_manage_destinations.php?msg=deleted");
exit;
