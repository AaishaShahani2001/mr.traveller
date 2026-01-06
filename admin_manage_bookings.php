<?php
session_start();
require "config.php";

/* ---------- Admin only ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* ---------- Filters ---------- */
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$month  = $_GET['month'] ?? 'all';
$year   = $_GET['year'] ?? date('Y');

/* ---------- Pagination ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

/* ---------- WHERE ---------- */
$where = "WHERE 1";
$params = [];

if ($status !== 'all') {
    $where .= " AND b.status = ?";
    $params[] = $status;
}
if ($search !== '') {
    $where .= " AND (u.full_name LIKE ? OR d.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($month !== 'all') {
    $where .= " AND MONTH(b.created_at) = ?";
    $params[] = $month;
}
if ($year !== 'all') {
    $where .= " AND YEAR(b.created_at) = ?";
    $params[] = $year;
}

/* ---------- Count ---------- */
$countStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM bookings b
    JOIN users u ON b.user_id=u.user_id
    JOIN destinations d ON b.dest_id=d.dest_id
    $where
");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ---------- Fetch ---------- */
$sql = "
SELECT 
    b.*,
    u.full_name,
    d.title AS destination,
    h.name AS hotel_name,
    DATEDIFF(b.check_out,b.check_in) AS nights
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN destinations d ON b.dest_id=d.dest_id
LEFT JOIN hotels h ON b.hotel_id=h.hotel_id
$where
ORDER BY b.booking_id DESC
LIMIT $perPage OFFSET $offset
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q($arr = []) {
    return http_build_query(array_merge($_GET, $arr));
}
function formatDate($d) {
    return $d ? date("d M, Y", strtotime($d)) : '—';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Bookings | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box;font-family:"Segoe UI",Arial}
body{margin:0;background:#f5f6fa}

/* Mobile Topbar */
.topbar{
    display:none;
    position:fixed;top:0;left:0;right:0;
    height:56px;background:#1f2937;color:white;
    align-items:center;padding:0 16px;z-index:1200;
}
.hamburger{font-size:22px;cursor:pointer;margin-right:12px}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1100}
.overlay.show{display:block}

/* Layout */
.layout{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{
    width:250px;background:#1f2937;color:white;
    padding-top:30px;position:fixed;height:100%;
    transition:.3s;z-index:1150;
}
.sidebar.show{transform:translateX(0)}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{display:block;padding:14px 22px;color:#e5e7eb;text-decoration:none}
.sidebar a:hover,.sidebar a.active{background:#2563eb;color:white}

/* Main */
.main{margin-left:250px;padding:24px;width:100%}

/* Filters */
.filters{
    max-width:1400px;margin:auto;
    background:white;padding:20px;border-radius:18px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.status-links a{
    margin-right:10px;
    padding:8px 16px;
    border-radius:999px;
    background:#f1f5f9;
    text-decoration:none;
    font-weight:700;
    color:#475569;
}
.status-links a.active{
    background:#2563eb;color:white;
}
.filter-box{
    margin-top:16px;
    display:flex;gap:10px;flex-wrap:wrap;
}
.filter-box input,.filter-box select{
    padding:10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
}
.filter-box button{
    padding:10px 22px;
    background:#2563eb;color:white;
    border:none;border-radius:10px;font-weight:800;
}

/* Table */
.table-box{
    max-width:1400px;margin:24px auto;
    background:white;border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    overflow-x:auto;
}
table{width:100%;border-collapse:collapse;min-width:1100px}
th{background:#2563eb;color:white;padding:14px;text-align:left}
td{padding:14px;border-bottom:1px solid #eee}

/* Status */
.status{padding:6px 14px;border-radius:999px;font-weight:700;font-size:13px}
.pending{background:#fff3cd;color:#856404}
.confirmed{background:#e9f9ee;color:#2e7d32}
.cancelled{background:#fdecea;color:#c0392b}

/* Buttons */
.actions{display:flex;flex-direction:column;gap:8px}
.btn{padding:8px 14px;border-radius:999px;border:none;font-weight:700;cursor:pointer}
.btn-approve{background:#22c55e;color:white}
.btn-cancel{background:#ef4444;color:white}

/* Mobile Cards */
.card-list{display:none}
.booking-card{
    background:white;border-radius:18px;padding:18px;
    margin-bottom:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.card-header{display:flex;justify-content:space-between;align-items:center}
.card-grid{
    margin-top:14px;
    display:grid;grid-template-columns:1fr 1fr;gap:10px;
}
.card-item{
    background:#f8fafc;padding:10px;border-radius:12px;
}
.card-actions{margin-top:14px;display:flex;gap:10px}
.card-actions button{flex:1}

/* Pagination */
.pagination{
    display:flex;justify-content:center;gap:8px;
    margin:20px 0;flex-wrap:wrap;
}
.pagination a{
    padding:10px 14px;border-radius:10px;
    background:white;border:1px solid #ddd;
    text-decoration:none;font-weight:700;color:#333;
}
.pagination a.active{background:#2563eb;color:white}

/* Responsive */
@media(max-width:900px){
    .topbar{display:flex}
    .sidebar{transform:translateX(-100%)}
    .main{margin-left:0;padding:20px;padding-top:78px}
    .table-box{display:none}
    .card-list{display:block}
}
</style>
</head>

<body>

<div class="topbar">
    <span class="hamburger" onclick="toggleMenu()">☰</span>
    <b>Bookings</b>
</div>
<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div class="layout">

<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a class="active" href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main">

<div class="filters">
    <div class="status-links">
        <?php foreach(['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled'] as $k=>$v): ?>
            <a class="<?= $status===$k?'active':'' ?>" href="?<?= q(['status'=>$k,'page'=>1]) ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <form class="filter-box">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <select name="month"><option value="all">All Months</option><?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>><?= date("F",mktime(0,0,0,$m,1)) ?></option>
        <?php endfor; ?></select>

        <select name="year"><?php for($y=date('Y');$y>=2022;$y--): ?>
            <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?></select>

        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
        <button>Apply</button>
    </form>
</div>

<div class="table-box">
<table>
<thead>
<tr>
    <th>User</th><th>Destination</th><th>Check-in</th><th>Check-out</th>
    <th>Nights</th><th>People</th><th>Total</th><th>Status</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($bookings as $b): ?>
<tr>
<td><?= $b['full_name'] ?></td>
<td><?= $b['destination'] ?></td>
<td><?= formatDate($b['check_in']) ?></td>
<td><?= formatDate($b['check_out']) ?></td>
<td><?= $b['nights'] ?></td>
<td><?= $b['number_of_people'] ?></td>
<td>$<?= number_format($b['total_amount'],2) ?></td>
<td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
<td>
<?php if($b['status']==='pending'): ?>
<button class="btn btn-approve" onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=confirmed'">Approve</button>
<button class="btn btn-cancel" onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=cancelled'">Cancel</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="card-list">
<?php foreach($bookings as $b): ?>
<div class="booking-card">
<b><?= $b['full_name'] ?></b>
<span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
<div class="card-grid">
<div class="card-item"><b>Destination</b><?= $b['destination'] ?></div>
<div class="card-item"><b>Total</b>$<?= number_format($b['total_amount'],2) ?></div>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a class="<?= $i==$page?'active':'' ?>" href="?<?= q(['page'=>$i]) ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

</div>
</div>

<script>
function toggleMenu(){
    sidebar.classList.toggle("show");
    overlay.classList.toggle("show");
}
</script>

</body>
</html>
