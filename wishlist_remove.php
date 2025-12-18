<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: wishlist_add.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$dest_id = (int)$_GET['id'];

/* ---------- Delete wishlist item ---------- */
$stmt = $conn->prepare("
    DELETE FROM wishlist 
    WHERE user_id = ? AND dest_id = ?
");
$stmt->execute([$user_id, $dest_id]);

header("Location: wishlist_add.php");
exit;
