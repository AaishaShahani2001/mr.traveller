<?php
session_start();
require "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_manage_travel_facilities.php");
    exit;
}

$id = (int)$_GET['id'];

/* 🔒 CHECK IF USED IN BOOKINGS */
$check = $conn->prepare("
    SELECT COUNT(*) 
    FROM bookings 
    WHERE facility_id = ?
");
$check->execute([$id]);

if ($check->fetchColumn() > 0) {
    header("Location: admin_manage_travel_facilities.php?msg=blocked");
    exit;
}

/* ✅ SAFE DELETE */
$del = $conn->prepare("DELETE FROM travel_facilities WHERE facility_id = ?");
$del->execute([$id]);

header("Location: admin_manage_travel_facilities.php?msg=deleted");
exit;
