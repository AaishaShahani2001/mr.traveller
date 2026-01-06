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
    f.transport_type,
    DATEDIFF(b.check_out,b.check_in) AS nights
FROM bookings b
JOIN users u ON b.user_id=u.user_id
JOIN destinations d ON b.dest_id=d.dest_id
LEFT JOIN hotels h ON b.hotel_id=h.hotel_id
LEFT JOIN travel_facilities f ON b.facility_id=f.facility_id
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
    return $d ? date("d M Y", strtotime($d)) : '—';
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

/* ===== MOBILE TOP BAR ===== */
.topbar{
    display:none;
    position:fixed;
    top:0;left:0;right:0;
    height:56px;
    background:#1f2937;
    color:white;
    align-items:center;
    padding:0 16px;
    z-index:1200;
}
.hamburger{font-size:22px;cursor:pointer;margin-right:12px}
.topbar-title{font-weight:800}

/* Overlay */
.overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    z-index:1100;
}
.overlay.show{display:block}

/* ===== Layout ===== */
.layout{display:flex;min-height:100vh}

/* ===== Sidebar (DESKTOP DEFAULT: VISIBLE) ===== */
.sidebar{
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    height:100%;
    z-index:1150;
}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{display:block;padding:14px 22px;color:#e5e7eb;text-decoration:none}
.sidebar a.active,.sidebar a:hover{background:#2563eb;color:white}

/* ===== Main ===== */
.main{margin-left:250px;padding:24px;width:100%}

/* ===== Filters ===== */
.filters{
    background:white;padding:20px;border-radius:18px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    margin-bottom:24px
}
.status-links a{
    margin-right:8px;padding:8px 16px;border-radius:999px;
    background:#f1f5f9;font-weight:700;text-decoration:none;color:#475569
}
.status-links a.active{background:#2563eb;color:white}
.filter-box{margin-top:14px;display:flex;gap:10px;flex-wrap:wrap}
.filter-box input, .filter-box select{
    padding:10px 14px;border-radius:10px;border:1px solid #ccc
}
.filter-box button{
    padding:10px 22px;border:none;border-radius:10px;
    background:#2563eb;color:white;font-weight:800
}

/* ===== Desktop Table ===== */
.table-box{
    background:white;border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    overflow-x:auto
}
table{width:100%;border-collapse:collapse;min-width:1100px}
th{background:#2563eb;color:white;padding:14px;text-align:left}
td{padding:14px;border-bottom:1px solid #eee}

/* ===== Status ===== */
.status{padding:6px 14px;border-radius:999px;font-weight:700;font-size:13px}
.pending{background:#fff3cd;color:#856404}
.confirmed{background:#e9f9ee;color:#2e7d32}
.cancelled{background:#fdecea;color:#c0392b}

/* ===== Buttons ===== */
.actions{display:flex;flex-direction:column;gap:8px}
.btn{padding:8px 14px;border-radius:999px;border:none;font-weight:700;cursor:pointer}
.btn-view{background:#eef2ff;color:#2563eb}
.btn-approve{background:#22c55e;color:white}
.btn-cancel{background:#ef4444;color:white}

/* ===== Pagination ===== */
.pagination{display:flex;justify-content:center;gap:8px;margin:20px 0;flex-wrap:wrap}
.pagination a{
    padding:10px 14px;border-radius:10px;
    background:white;border:1px solid #ddd;
    text-decoration:none;font-weight:700;color:#333
}
.pagination a.active{background:#2563eb;color:white}

/* ===== MOBILE CARDS ===== */
.card-list{display:none}
.booking-card{
    background:white;border-radius:18px;padding:18px;
    margin-bottom:16px;box-shadow:0 12px 30px rgba(0,0,0,.12)
}
.card-header{display:flex;justify-content:space-between;align-items:center}
.card-header h4{margin:0;font-size:16px;font-weight:800}
.card-destination{margin-top:4px;font-size:13px;color:#6b7280}
.card-grid{
    margin-top:14px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px
}
.card-item{
    background:#f8fafc;padding:10px;border-radius:12px;font-size:14px
}
.card-item b{
    display:block;font-size:12px;color:#6b7280;margin-bottom:4px
}
.card-actions{margin-top:14px;display:flex;gap:10px}
.card-actions button{flex:1}

/* ===== MODAL (DESKTOP VIEW BUTTON) ===== */
.modal-bg{
    position:fixed;inset:0;background:rgba(0,0,0,.6);
    display:none;align-items:center;justify-content:center;z-index:9999;
    padding:18px;
}
.modal-bg.show{display:flex}
.modal{
    background:white;border-radius:16px;padding:22px;
    max-width:420px;width:100%;
}
.modal h3{margin-top:0}
.modal p{margin:6px 0}
.modal button{
    margin-top:14px;width:100%;padding:10px;border:none;
    background:#2563eb;color:white;border-radius:999px;font-weight:800;cursor:pointer;
}

/* ===== RESPONSIVE (MOBILE SIDEBAR OFF-CANVAS) ===== */
@media(max-width:900px){
    .topbar{display:flex}

    .sidebar{
        transform:translateX(-100%);
        transition:.3s ease;
    }
    .sidebar.open{transform:translateX(0)}

    .main{
        margin-left:0;
        padding:20px;
        padding-top:78px;
    }

    .table-box{display:none}
    .card-list{display:block}
}
</style>
</head>

<body>

<!-- Mobile Topbar -->
<div class="topbar">
    <span class="hamburger" onclick="toggleMenu()">☰</span>
    <span class="topbar-title">Bookings</span>
</div>
<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div class="layout">

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a class="active" href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
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
        <input type="hidden" name="status" value="<?= $status ?>">
        <input type="text" name="search" placeholder="Search user or destination" value="<?= htmlspecialchars($search) ?>">

        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <select name="month"><option value="all">All Months</option><?php for($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>><?= date("F",mktime(0,0,0,$m,1)) ?></option>
        <?php endfor; ?></select>

        <select name="year"><?php for($y=date('Y');$y>=2022;$y--): ?>
            <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?></select>

        <button>Apply</button>
    </form>
</div>

<!-- ===== DESKTOP TABLE ===== -->
<div class="table-box">
<table>
<thead>
<tr>
    <th>User</th>
    <th>Destination</th>
    <th>Check-in</th>
    <th>Check-out</th>
    <th>Nights</th>
    <th>Total</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['full_name']) ?></td>
<td><?= htmlspecialchars($b['destination']) ?></td>
<td><?= formatDate($b['check_in']) ?></td>
<td><?= formatDate($b['check_out']) ?></td>
<td><?= $b['nights'] ?></td>
<td>$<?= number_format($b['total_amount']) ?></td>
<td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
<td>
<div class="actions">
    <button class="btn btn-view" onclick='openView(<?= json_encode($b) ?>)'>View</button>
    <?php if($b['status']==='pending'): ?>
        <button class="btn btn-approve"
            onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=confirmed'">Approve</button>
        <button class="btn btn-cancel"
            onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=cancelled'">Cancel</button>
    <?php endif; ?>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ===== MOBILE CARDS ===== -->
<div class="card-list">
<?php foreach($bookings as $b): ?>
<div class="booking-card">
    <div class="card-header">
        <h4><?= htmlspecialchars($b['full_name']) ?></h4>
        <span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
    </div>

    <div class="card-destination"><?= htmlspecialchars($b['destination']) ?></div>

    <div class="card-grid">
        <div class="card-item"><b>Check-in</b><?= formatDate($b['check_in']) ?></div>
        <div class="card-item"><b>Check-out</b><?= formatDate($b['check_out']) ?></div>
        <div class="card-item"><b>Nights</b><?= $b['nights'] ?></div>
        <div class="card-item"><b>People</b><?= $b['number_of_people'] ?></div>
        <div class="card-item"><b>Total</b>$<?= number_format($b['total_amount']) ?></div>
        <div class="card-item"><b>Hotel</b><?= $b['hotel_name'] ?: 'N/A' ?></div>
    </div>

    <?php if($b['status']==='pending'): ?>
    <div class="card-actions">
        <button class="btn btn-approve"
            onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=confirmed'">Approve</button>
        <button class="btn btn-cancel"
            onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=cancelled'">Cancel</button>
    </div>
    <?php endif; ?>
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

<!-- VIEW MODAL (DESKTOP VIEW BUTTON USES THIS) -->
<div class="modal-bg" id="viewModal" onclick="if(event.target.id==='viewModal') closeView()">
    <div class="modal" id="modalContent"></div>
</div>

<script>
function toggleMenu(){
    // only matters on mobile 
    document.getElementById("sidebar").classList.toggle("open");
    document.getElementById("overlay").classList.toggle("show");
}
function openView(b){
    document.getElementById('modalContent').innerHTML = `
        <h3>Booking Details</h3>
        <p><b>User:</b> ${b.full_name}</p>
        <p><b>Destination:</b> ${b.destination}</p>
        <p><b>Hotel:</b> ${b.hotel_name ?? 'N/A'}</p>
        <p><b>Transport:</b> ${b.transport_type ?? 'N/A'}</p>
        <p><b>Check-in:</b> ${b.check_in}</p>
        <p><b>Check-out:</b> ${b.check_out}</p>
        <p><b>People:</b> ${b.number_of_people}</p>
        <p><b>Total:</b> $${b.total_amount}</p>
        <button onclick="closeView()">Close</button>
    `;
    document.getElementById('viewModal').classList.add('show');
}
function closeView(){
    document.getElementById('viewModal').classList.remove('show');
}
</script>

</body>
</html>
