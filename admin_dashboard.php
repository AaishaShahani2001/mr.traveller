<?php 
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* ---------- Selected Year ---------- */
$selectedYear = $_GET['year'] ?? date('Y');

/* ---------- Available Years ---------- */
$yearStmt = $conn->query("
    SELECT DISTINCT YEAR(created_at) y
    FROM bookings
    ORDER BY y DESC
");
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

/* ---------- Stats ---------- */
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalDestinations = $conn->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalHotels = $conn->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
$totalFacilities = $conn->query("SELECT COUNT(*) FROM travel_facilities")->fetchColumn();
$totalBookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

/* ---------- Revenue ---------- */
$totalRevenue = $conn->query("
    SELECT IFNULL(SUM(total_amount),0)
    FROM bookings WHERE status='confirmed'
")->fetchColumn();

$monthlyRevenue = $conn->prepare("
    SELECT IFNULL(SUM(total_amount),0)
    FROM bookings
    WHERE status='confirmed'
      AND MONTH(created_at)=MONTH(CURRENT_DATE())
      AND YEAR(created_at)=?
");
$monthlyRevenue->execute([$selectedYear]);
$monthlyRevenue = $monthlyRevenue->fetchColumn();

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

/* ---------- Monthly Bookings (Year Filtered) ---------- */
$bookingChart = array_fill(1, 12, 0);
$stmt = $conn->prepare("
    SELECT MONTH(created_at) m, COUNT(*) total
    FROM bookings
    WHERE YEAR(created_at)=?
    GROUP BY MONTH(created_at)
");
$stmt->execute([$selectedYear]);
foreach ($stmt as $row) {
    $bookingChart[(int)$row['m']] = (int)$row['total'];
}

/* ---------- Monthly Revenue (Year Filtered) ---------- */
$revenueChart = array_fill(1, 12, 0);
$stmt = $conn->prepare("
    SELECT MONTH(created_at) m, SUM(total_amount) total
    FROM bookings
    WHERE status='confirmed'
      AND YEAR(created_at)=?
    GROUP BY MONTH(created_at)
");
$stmt->execute([$selectedYear]);
foreach ($stmt as $row) {
    $revenueChart[(int)$row['m']] = (float)$row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{box-sizing:border-box;font-family:"Segoe UI",Arial}
body{margin:0;background:#f5f6fa}
.layout{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{
    width:250px;background:#1f2937;color:white;
    padding-top:30px;position:fixed;height:100%;
}
.sidebar a{
    display:block;padding:14px 22px;color:#e5e7eb;text-decoration:none;
}
.sidebar a.active,.sidebar a:hover{background:#2563eb;color:white}

/* Main */
.main{margin-left:250px;padding:24px;width:100%}

/* Cards */
.cards,.revenue{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}
.card{
    background:white;padding:20px;border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.card h3{margin:0;font-size:15px;color:#555}
.card span{font-size:30px;font-weight:bold}

/* Section */
.section{
    background:white;margin-top:25px;padding:20px;
    border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.12);
}

/* Year Filter */
.year-filter{
    display:flex;
    justify-content:flex-end;
    margin-bottom:12px;
}
.year-filter select{
    padding:8px 14px;
    border-radius:10px;
    border:1px solid #ccc;
    font-weight:bold;
}

/* Table */
table{width:100%;border-collapse:collapse}
th,td{padding:12px;border-bottom:1px solid #eee}
th{text-align:left;color:#555}

/* Status */
.status{
    padding:5px 10px;border-radius:20px;
    font-size:12px;font-weight:bold;
}
.pending{background:#fff3cd;color:#856404}
.confirmed{background:#e9f9ee;color:#2e7d32}
.cancelled{background:#fdecea;color:#c0392b}
</style>
</head>

<body>

<div class="layout">

<div class="sidebar">
    <h2 style="text-align:center">Admin Panel</h2>
    <a class="active" href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">

<div class="cards">
    <div class="card"><h3>Total Users</h3><span><?= $totalUsers ?></span></div>
    <div class="card"><h3>Total Destinations</h3><span><?= $totalDestinations ?></span></div>
    <div class="card"><h3>Total Hotels</h3><span><?= $totalHotels ?></span></div>
    <div class="card"><h3>Total Facilities</h3><span><?= $totalFacilities ?></span></div>
    <div class="card"><h3>Total Bookings</h3><span><?= $totalBookings ?></span></div>
</div>

<div class="revenue" style="margin-top:25px">
    <div class="card"><h3>Total Revenue</h3><span>$<?= number_format($totalRevenue) ?></span></div>
    <div class="card"><h3>This Month</h3><span>$<?= number_format($monthlyRevenue) ?></span></div>
    <div class="card"><h3>Pending Revenue</h3><span>$<?= number_format($pendingRevenue) ?></span></div>
</div>

<!-- ===== YEAR SELECTOR ===== -->
<div class="year-filter">
<form method="get">
    <select name="year" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?>
            <option value="<?= $y ?>" <?= $y==$selectedYear?'selected':'' ?>>
                <?= $y ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
</div>

<div class="section">
    <h3>Monthly Bookings (<?= $selectedYear ?>)</h3>
    <canvas id="bookingChart"></canvas>
</div>

<div class="section">
    <h3>Revenue Per Month (<?= $selectedYear ?>)</h3>
    <canvas id="revenueChart"></canvas>
</div>

<div class="section">
    <h3>Recent Bookings</h3>
    <table>
        <tr>
            <th>User</th><th>Destination</th><th>Amount</th><th>Status</th>
        </tr>
        <?php foreach($recentBookings as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['full_name']) ?></td>
            <td><?= htmlspecialchars($b['title']) ?></td>
            <td>$<?= number_format($b['total_amount']) ?></td>
            <td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</div>
</div>

<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

new Chart(document.getElementById('bookingChart'),{
    type:'bar',
    data:{
        labels:months,
        datasets:[{
            label:'Bookings',
            data:<?= json_encode(array_values($bookingChart)) ?>,
            backgroundColor:'#2563eb'
        }]
    }
});

new Chart(document.getElementById('revenueChart'),{
    type:'line',
    data:{
        labels:months,
        datasets:[{
            label:'Revenue ($)',
            data:<?= json_encode(array_values($revenueChart)) ?>,
            borderColor:'#22c55e',
            backgroundColor:'rgba(34,197,94,.15)',
            fill:true,
            tension:.4
        }]
    }
});
</script>

</body>
</html>
