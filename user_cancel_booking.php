<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Booking ID missing!");
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Only allow cancelling own pending booking
$stmt = $conn->prepare("
    UPDATE bookings 
    SET status='cancelled' 
    WHERE booking_id=? AND user_id=? AND status='pending'
");
$stmt->execute([$booking_id, $user_id]);

header("Location: my_bookings.php?msg=cancelled");
exit;
