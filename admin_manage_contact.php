<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ---------- Fetch messages ---------- */
$messages = $conn->query("
    SELECT * FROM contact_messages
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Messages | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }
body { margin:0; background:#f5f6fa; }

.layout { display:flex; min-height:100vh; }

/* Sidebar */
.sidebar {
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    height:100%;
}
.sidebar h2 { text-align:center; margin-bottom:30px; }
.sidebar a {
    display:block;
    padding:14px 22px;
    color:#e5e7eb;
    text-decoration:none;
}
.sidebar a:hover,
.sidebar a.active {
    background:#2563eb;
    color:white;
}

/* Main */
.main {
    margin-left:250px;
    padding:24px;
    width:100%;
}

/* Section */
.section {
    background:white;
    padding:24px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}

.section h3 {
    margin-bottom:20px;
}

/* Table */
table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    padding:14px;
    border-bottom:1px solid #eee;
    text-align:left;
    vertical-align:top;
}

th {
    background:#f3f4f6;
    color:#333;
    font-size:14px;
}

td {
    font-size:14px;
    color:#444;
}

.message {
    max-width:420px;
    line-height:1.5;
    color:#555;
}

/* Empty */
.empty {
    text-align:center;
    color:#777;
    padding:30px;
}

/* Responsive */
@media (max-width:900px) {
    .sidebar {
        position:relative;
        width:100%;
        height:auto;
    }
    .main {
        margin-left:0;
    }
    .layout {
        flex-direction:column;
    }
    table {
        font-size:13px;
    }
}
</style>
</head>

<body>

<div class="layout">

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a class="active" href="admin_manage_contact.php">📩 Messages</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main -->
<div class="main">

<div class="section">
    <h3>Contact Messages</h3>

    <?php if (!$messages): ?>
        <div class="empty">No messages received yet.</div>
    <?php else: ?>
    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Date</th>
        </tr>

        <?php foreach ($messages as $m): ?>
        <tr>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><?= htmlspecialchars($m['email']) ?></td>
            <td><?= htmlspecialchars($m['subject'] ?: '-') ?></td>
            <td class="message"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
            <td><?= date("d M Y, h:i A", strtotime($m['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

</div>

</div>
</div>

</body>
</html>
