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
*{box-sizing:border-box;font-family:"Segoe UI",Arial}
body{margin:0;background:#f5f6fa}

.layout{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    height:100%;
}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{
    display:block;
    padding:14px 22px;
    color:#e5e7eb;
    text-decoration:none;
}
.sidebar a:hover,.sidebar a.active{
    background:#2563eb;
    color:white;
}

/* ===== MAIN ===== */
.main{
    margin-left:250px;
    padding:24px;
    width:100%;
}

/* ===== SECTION ===== */
.section{
    background:white;
    padding:24px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    max-width:1200px;
    margin:auto;
}
.section h3{margin-bottom:20px}

/* ===== TABLE (DESKTOP) ===== */
.table-wrap{
    overflow-x:auto;
}
table{
    width:100%;
    border-collapse:collapse;
}
th,td{
    padding:14px;
    border-bottom:1px solid #eee;
    text-align:left;
    vertical-align:top;
}
th{
    background:#f3f4f6;
    font-size:14px;
}
td{
    font-size:14px;
    color:#444;
}
.message{
    max-width:420px;
    line-height:1.5;
}

/* ===== MOBILE CARDS ===== */
.card-list{display:none}
.msg-card{
    background:#f9fafb;
    padding:18px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
    margin-bottom:16px;
}
.msg-card h4{
    margin:0 0 6px;
    font-size:16px;
}
.msg-card p{
    margin:6px 0;
    font-size:14px;
    color:#444;
}
.msg-card .date{
    margin-top:10px;
    font-size:12px;
    color:#777;
}

/* ===== EMPTY ===== */
.empty{
    text-align:center;
    color:#777;
    padding:30px;
}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }
    .main{
        margin-left:0;
    }
    .layout{
        flex-direction:column;
    }
    .table-wrap{
        display:none;
    }
    .card-list{
        display:block;
    }
}
</style>
</head>

<body>

<div class="layout">

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a class="active" href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- ===== MAIN ===== -->
<div class="main">
<div class="section">
<h3>Contact Messages</h3>

<?php if (!$messages): ?>
    <div class="empty">No messages received yet.</div>
<?php else: ?>

<!-- ===== DESKTOP TABLE ===== -->
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Subject</th>
    <th>Message</th>
    <th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach ($messages as $m): ?>
<tr>
    <td><?= htmlspecialchars($m['name']) ?></td>
    <td><?= htmlspecialchars($m['email']) ?></td>
    <td><?= htmlspecialchars($m['subject'] ?: '-') ?></td>
    <td class="message"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
    <td><?= date("d M Y, h:i A", strtotime($m['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ===== MOBILE CARD VIEW ===== -->
<div class="card-list">
<?php foreach ($messages as $m): ?>
<div class="msg-card">
    <h4><?= htmlspecialchars($m['name']) ?></h4>
    <p><strong>Email:</strong> <?= htmlspecialchars($m['email']) ?></p>
    <p><strong>Subject:</strong> <?= htmlspecialchars($m['subject'] ?: '-') ?></p>
    <p><?= nl2br(htmlspecialchars($m['message'])) ?></p>
    <div class="date">
        <?= date("d M Y, h:i A", strtotime($m['created_at'])) ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

</div>
</div>

</div>
</body>
</html>
