<?php
session_start();
require "config.php";

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Count stats
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalDestinations = $conn->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Mr.Traveller</title>

    <style>
        body { margin:0; font-family:Arial; display:flex; background:#f5f6fa; }

        /* Sidebar */
        .sidebar {
            width:250px;
            height:100vh;
            background:#2c3e50;
            color:white;
            padding-top:30px;
            position:fixed;
        }

        .sidebar h2 {
            text-align:center;
            margin-bottom:20px;
        }

        .sidebar a {
            display:block;
            padding:12px 20px;
            color:white;
            text-decoration:none;
            font-size:16px;
        }
        .sidebar a:hover {
            background:#1abc9c;
        }

        /* Main content */
        .main {
            margin-left:250px;
            padding:30px;
            width:100%;
        }

        .topbar {
            background:white;
            padding:15px;
            border-radius:10px;
            margin-bottom:20px;
            box-shadow:0 0 6px rgba(0,0,0,0.1);
        }

        /* Stats Cards */
        .cards {
            display:flex;
            gap:20px;
        }

        .card {
            width:220px;
            background:white;
            padding:20px;
            border-radius:10px;
            text-align:center;
            box-shadow:0 0 6px rgba(0,0,0,0.1);
        }

        .card h3 { margin-bottom:10px; }
        .card span { font-size:32px; color:#2980b9; }

    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>

    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📌 Destinations</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>Welcome, Admin <?php echo $_SESSION['name']; ?></h2>
    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Users</h3>
            <span><?php echo $totalUsers; ?></span>
        </div>

        <div class="card">
            <h3>Total Destinations</h3>
            <span><?php echo $totalDestinations; ?></span>
        </div>

        <div class="card">
            <h3>Total Bookings</h3>
            <span><?php echo $totalBookings; ?></span>
        </div>

    </div>

</div>

</body>
</html>
