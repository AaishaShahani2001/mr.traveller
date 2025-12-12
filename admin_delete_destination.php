<?php
session_start();
require "config.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'];

// delete image file also
$img = $conn->prepare("SELECT image FROM destinations WHERE dest_id=?");
$img->execute([$id]);
$row = $img->fetch();

if ($row && file_exists("uploads/" . $row['image'])) {
    unlink("uploads/" . $row['image']);
}

$del = $conn->prepare("DELETE FROM destinations WHERE dest_id=?");
$del->execute([$id]);

header("Location: admin_manage_destinations.php");
exit;
?>
