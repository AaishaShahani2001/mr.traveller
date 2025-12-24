<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid hotel ID");
}

$hotel_id = (int)$_GET['id'];

/* Delete hotel */
$delete = $conn->prepare("DELETE FROM hotels WHERE hotel_id = ?");
$delete->execute([$hotel_id]);

header("Location: manage_hotels.php?msg=deleted");
exit;
