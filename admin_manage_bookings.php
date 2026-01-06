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

/* ---------- Fetch ---------- */
$sql = "
SELECT 
    b.*,
    u.full_name,
    d.title AS destination,
    h.name AS hotel_name,
    h.type AS hotel_type,
    f.transport_type,
    f.provider_name,
    DATEDIFF(b.check_out, b.check_in) AS nights
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN destinations d ON b.dest_id = d.dest_id
LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
LEFT JOIN travel_facilities f ON b.facility_id = f.facility_id
$where
ORDER BY b.booking_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q($arr = []) {
    return http_build_query(array_merge($_GET, $arr));
}
function formatDate($date) {
    return $date ? date("d M, Y", strtotime($date)) : '—';
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
.layout{display:flex;min-height:100vh}

/* ===== TOPBAR (MOBILE) ===== */
.topbar{
    display:none;
    background:#1f2937;
    color:white;
    padding:14px 18px;
    align-items:center;
    justify-content:space-between;
}
.menu-btn{
    font-size:22px;
    cursor:pointer;
}

/* ===== SIDEBAR ===== */
.sidebar{
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    height:100%;
    transition:.3s;
}
.sidebar.hide{transform:translateX(-100%)}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{
    display:block;padding:14px 22px;
    color:#e5e7eb;text-decoration:none
}
.sidebar a:hover,.sidebar a.active{
    background:#2563eb;color:white
}

/* ===== MAIN ===== */
.main{margin-left:250px;padding:24px;width:100%}

/* ===== FILTERS (UNCHANGED) ===== */
.filters{
    max-width:1400px;margin:auto;
    background:white;padding:18px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    display:flex;justify-content:space-between;
    flex-wrap:wrap;gap:16px;
}
.status-links a{
    margin-right:14px;font-weight:700;
    text-decoration:none;color:#6b7280
}
.status-links a.active{color:#2563eb}
.filter-box{display:flex;gap:10px;flex-wrap:wrap}
.filter-box input,.filter-box select{
    padding:10px 14px;border-radius:10px;border:1px solid #ccc
}
.filter-box button{
    padding:10px 20px;background:#2563eb;
    color:white;border:none;border-radius:10px;
    font-weight:700
}

/* ===== TABLE (DESKTOP) ===== */
.table-box{
    max-width:1400px;margin:24px auto;
    background:white;border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    overflow-x:auto
}
table{width:100%;border-collapse:collapse;min-width:1100px}
th{background:#2563eb;color:white;padding:14px;text-align:left}
td{padding:14px;border-bottom:1px solid #eee}

/* ===== STATUS ===== */
.status{
    padding:6px 14px;border-radius:999px;
    font-weight:700;font-size:13px
}
.pending{background:#fff3cd;color:#856404}
.confirmed{background:#e9f9ee;color:#2e7d32}
.cancelled{background:#fdecea;color:#c0392b}

/* ===== ACTIONS ===== */
.actions{display:flex;flex-direction:column;gap:8px}
.btn{
    padding:8px 14px;border-radius:999px;
    border:none;font-weight:700;cursor:pointer
}
.btn-view{background:#eef2ff;color:#2563eb}
.btn-approve{background:#22c55e;color:white}
.btn-cancel{background:#ef4444;color:white}

/* ===== MOBILE CARD VIEW ===== */
.card-list{display:none}
.booking-card{
    background:white;border-radius:16px;
    padding:18px;margin-bottom:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12)
}
.card-header{
    display:flex;justify-content:space-between;
    align-items:center;margin-bottom:6px
}
.card-header h4{margin:0;font-size:16px}
.card-meta{font-size:13px;color:#6b7280}
.card-grid{
    margin-top:12px;display:grid;
    grid-template-columns:1fr 1fr;gap:10px
}
.card-item{
    background:#f8fafc;padding:10px;
    border-radius:12px;font-size:14px
}
.card-item b{
    display:block;font-size:12px;
    color:#6b7280;margin-bottom:4px
}
.card-actions{margin-top:14px;display:flex;gap:10px}
.card-actions button{flex:1}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .topbar{display:flex}
    .sidebar{position:fixed;z-index:1000}
    .main{margin-left:0}
    .table-box{display:none}
    .card-list{display:block}
}
</style>
</head>

<body>

<!-- ===== MOBILE TOPBAR ===== -->
<div class="topbar">
    <span class="menu-btn" onclick="toggleSidebar()">☰</span>
    <strong>Admin Panel</strong>
</div>

<div class="layout">

<!-- ===== SIDEBAR ===== -->
<div class="sidebar hide" id="sidebar">
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

<!-- ===== MAIN ===== -->
<div class="main">

<!-- FILTERS (UNCHANGED) -->
<div class="filters">
    <div class="status-links">
        <?php foreach(['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled'] as $k=>$v): ?>
            <a class="<?= $status===$k?'active':'' ?>" href="?<?= q(['status'=>$k]) ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <form class="filter-box">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <select name="month">
            <option value="all">All Months</option>
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>>
                    <?= date("F", mktime(0,0,0,$m,1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="year">
            <?php for($y=date('Y');$y>=2022;$y--): ?>
                <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <input type="text" name="search" placeholder="Search user or destination" value="<?= htmlspecialchars($search) ?>">
        <button>Apply</button>
    </form>
</div>

<!-- DESKTOP TABLE -->
<div class="table-box">
<table>
<thead>
<tr>
    <th>User</th><th>Destination</th><th>Check-in</th><th>Check-out</th>
    <th>Nights</th><th>People</th><th>Total</th><th>Status</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($bookings as $b): ?>
<tr>
    <td><?= htmlspecialchars($b['full_name']) ?></td>
    <td><?= htmlspecialchars($b['destination']) ?></td>
    <td><?= formatDate($b['check_in']) ?></td>
    <td><?= formatDate($b['check_out']) ?></td>
    <td><?= $b['nights'] ?></td>
    <td><?= $b['number_of_people'] ?></td>
    <td>$<?= number_format($b['total_amount'],2) ?></td>
    <td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
    <td>
        <div class="actions">
            <?php if ($b['status']==='pending'): ?>
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

<!-- MOBILE CARDS -->
<div class="card-list">
<?php foreach ($bookings as $b): ?>
<div class="booking-card">
    <div class="card-header">
        <h4><?= htmlspecialchars($b['full_name']) ?></h4>
        <span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
    </div>
    <div class="card-meta"><?= htmlspecialchars($b['destination']) ?></div>

    <div class="card-grid">
        <div class="card-item"><b>Check-in</b><?= formatDate($b['check_in']) ?></div>
        <div class="card-item"><b>Check-out</b><?= formatDate($b['check_out']) ?></div>
        <div class="card-item"><b>Nights</b><?= $b['nights'] ?></div>
        <div class="card-item"><b>People</b><?= $b['number_of_people'] ?></div>
        <div class="card-item"><b>Total</b>$<?= number_format($b['total_amount'],2) ?></div>
        <div class="card-item"><b>Hotel</b><?= $b['hotel_name'] ?: 'N/A' ?></div>
    </div>

    <?php if ($b['status']==='pending'): ?>
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

</div>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('hide');
}
</script>

</body>
</html>
