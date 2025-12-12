<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("No destination selected!");
}

$user_id = $_SESSION['user_id'];
$dest_id = $_GET['id'];

// Check if already in wishlist
$check = $conn->prepare("SELECT * FROM wishlist WHERE user_id=? AND dest_id=?");
$check->execute([$user_id, $dest_id]);

if ($check->rowCount() > 0) {
    echo "<script>alert('Already in your wishlist!'); window.history.back();</script>";
    exit;
}

// Insert into wishlist
$sql = $conn->prepare("INSERT INTO wishlist(user_id, dest_id) VALUES(?, ?)");
$sql->execute([$user_id, $dest_id]);

echo "<script>alert('Added to wishlist!'); window.history.back();</script>";
?>
