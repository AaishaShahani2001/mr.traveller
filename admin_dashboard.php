<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ---------- Stats ---------- */
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalDestinations = $conn->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

/* ---------- Revenue ---------- */
$totalRevenue = $conn->query("
    SELECT IFNULL(SUM(total_amount),0) 
    FROM bookings WHERE status='confirmed'
")->fetchColumn();

$monthlyRevenue = $conn->query("
    SELECT IFNULL(SUM(total_amount),0) 
    FROM bookings 
    WHERE status='confirmed'
      AND MONTH(created_at)=MONTH(CURRENT_DATE())
      AND YEAR(created_at)=YEAR(CURRENT_DATE())
")->fetchColumn();

$pendingRevenue = $conn->query("
    SELECT IFNULL(SUM(total_amount),0) 
    FROM bookings WHERE status='pending'
")->fetchColumn();

/* ---------- Recent Bookings ---------- */
$recentBookings = $conn->query("
    SELECT b.booking_id, u.full_name, d.title, b.total_amount, b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN destinations d ON b.dest_id = d.dest_id
    ORDER BY b.booking_id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Monthly bookings for chart ---------- */
$chartData = array_fill(1, 12, 0);

$stmt = $conn->query("
    SELECT MONTH(created_at) m, COUNT(*) total
    FROM bookings
    GROUP BY MONTH(created_at)
");

foreach ($stmt as $row) {
    $chartData[(int)$row['m']] = (int)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }
body { margin:0; background:#f5f6fa; }

.layout { display:flex; min-height:100vh; }

/* Sidebar */
.sidebar {
    width:250px; background:#1f2937; color:white;
    padding-top:30px; position:fixed; height:100%;
}
.sidebar h2 { text-align:center; margin-bottom:30px; }
.sidebar a {
    display:block; padding:14px 22px; color:#e5e7eb;
    text-decoration:none;
}
.sidebar a:hover, .sidebar a.active {
    background:#2563eb; color:white;
}

/* Main */
.main { margin-left:250px; padding:24px; width:100%; }

/* Cards */
.cards {
    display:grid;
    grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}
.card {
    background:white; padding:20px; border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.card h3 { margin:0; font-size:15px; color:#555; }
.card span { font-size:30px; font-weight:bold; }

/* Revenue */
.revenue {
    display:grid;
    grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-top:25px;
}

/* Section box */
.section {
    background:white; margin-top:25px;
    padding:20px; border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}

/* Table */
table {
    width:100%; border-collapse:collapse;
}
th, td {
    padding:12px; border-bottom:1px solid #eee;
}
th { text-align:left; color:#555; }

.status {
    padding:5px 10px; border-radius:20px;
    font-size:12px; font-weight:bold;
}
.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#e9f9ee; color:#2e7d32; }
.cancelled { background:#fdecea; color:#c0392b; }

/* Responsive */
@media(max-width:900px){
    .sidebar { position:relative; width:100%; }
    .main { margin-left:0; }
    .layout { flex-direction:column; }
}
</style>
</head>

<body>

<div class="layout">

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a class="active" href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- Main -->
<div class="main">

<!-- Stats -->
<div class="cards">
    <div class="card"><h3>Total Users</h3><span><?= $totalUsers ?></span></div>
    <div class="card"><h3>Total Destinations</h3><span><?= $totalDestinations ?></span></div>
    <div class="card"><h3>Total Bookings</h3><span><?= $totalBookings ?></span></div>
</div>

<!-- Revenue -->
<div class="revenue">
    <div class="card"><h3>Total Revenue</h3><span>$<?= number_format($totalRevenue,2) ?></span></div>
    <div class="card"><h3>This Month</h3><span>$<?= number_format($monthlyRevenue,2) ?></span></div>
    <div class="card"><h3>Pending Revenue</h3><span>$<?= number_format($pendingRevenue,2) ?></span></div>
</div>

<!-- Chart -->
<div class="section">
    <h3>Monthly Bookings</h3>
    <canvas id="bookingChart" height="100"></canvas>
</div>

<!-- Recent bookings -->
<div class="section">
    <h3>Recent Bookings</h3>
    <table>
        <tr>
            <th>User</th>
            <th>Destination</th>
            <th>Amount</th>
            <th>Status</th>
        </tr>
        <?php foreach ($recentBookings as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['full_name']) ?></td>
            <td><?= htmlspecialchars($b['title']) ?></td>
            <td>$<?= number_format($b['total_amount'],2) ?></td>
            <td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</div>
</div>

<script>
new Chart(document.getElementById('bookingChart'), {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Bookings',
            data: <?= json_encode(array_values($chartData)) ?>,
            backgroundColor: '#2563eb'
        }]
    },
    options: { responsive:true }
});
</script>

</body>
</html>
