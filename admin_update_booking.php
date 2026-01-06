<?php
session_start();
require "config.php";

// Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

// Validate input
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    die("Invalid request!");
}

$booking_id = $_GET['id'];
$status = $_GET['status'];

// Allow only valid statuses
$allowed = ['confirmed', 'cancelled'];
if (!in_array($status, $allowed)) {
    die("Invalid status!");
}

// Update booking
$stmt = $conn->prepare("UPDATE bookings SET status=? WHERE booking_id=?");
$stmt->execute([$status, $booking_id]);

header("Location: admin_manage_bookings.php?msg=updated");
exit;
