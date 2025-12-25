<?php
session_start();
require "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid facility ID");
}

$facility_id = (int)$_GET['id'];

$delete = $conn->prepare("DELETE FROM travel_facilities WHERE facility_id = ?");
$delete->execute([$facility_id]);

header("Location: admin_manage_travel_facilities.php?msg=deleted");
exit;
