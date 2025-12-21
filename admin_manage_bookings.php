<?php
session_start();
require "config.php";

/* ---------- Admin only ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ---------- Toast ---------- */
$msg = $_GET['msg'] ?? "";

/* ---------- Filters ---------- */
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$month  = $_GET['month'] ?? 'all';
$year   = $_GET['year'] ?? date('Y');

/* ---------- Pagination ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
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
$countSql = "
SELECT COUNT(*)
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN destinations d ON b.dest_id = d.dest_id
$where
";
$stmt = $conn->prepare($countSql);
$stmt->execute($params);
$totalRows = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ---------- Fetch ---------- */
$sql = "
SELECT 
    b.*, 
    u.full_name, 
    d.title,
    DATEDIFF(b.check_out, b.check_in) AS nights
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN destinations d ON b.dest_id = d.dest_id
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Bookings | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial, sans-serif; }
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

/* Filters */
.filters {
    max-width:1200px;
    margin:auto;
    background:white;
    padding:16px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
    display:grid;
    grid-template-columns:1fr auto;
    gap:14px;
}

.status-links a {
    margin-right:12px;
    font-weight:bold;
    text-decoration:none;
    color:#555;
}
.status-links a.active { color:#007bff; }

.filter-box {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.filter-box input,
.filter-box select {
    padding:10px;
    border-radius:10px;
    border:1px solid #ccc;
}
.filter-box button {
    padding:10px 18px;
    background:#007bff;
    color:white;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

/* Table */
.table-box {
    max-width:1200px;
    margin:18px auto;
    background:white;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
    overflow:hidden;
}
table { width:100%; border-collapse:collapse; }
th {
    background:#8e44ad; color:white;
    padding:14px; text-align:left;
}
td { padding:14px; border-bottom:1px solid #eee; }

/* Status */
.status {
    font-weight:bold;
    padding:6px 14px;
    border-radius:20px;
}
.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#e9f9ee; color:#2e7d32; }
.cancelled { background:#fdecea; color:#c0392b; }

/* Actions */
.actions { display:flex; gap:8px; }
.btn-action {
    padding:8px 16px;
    border-radius:999px;
    border:none;
    font-weight:600;
    cursor:pointer;
}
.btn-approve { background:#22c55e; color:white; }
.btn-cancel { background:#ef4444; color:white; }

/* Modal */
.modal-bg {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.6);
    display:none;
    align-items:center;
    justify-content:center;
}
.modal-bg.show { display:flex; }
.modal {
    background:white;
    padding:24px;
    border-radius:16px;
    max-width:420px;
    width:100%;
}
.modal-actions {
    display:flex;
    justify-content:flex-end;
    gap:10px;
}

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

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a class="active" href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="filters">
    <div class="status-links">
        <?php foreach (['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled'] as $k=>$v): ?>
            <a class="<?= $status===$k?'active':'' ?>" href="?<?= q(['status'=>$k,'page'=>1]) ?>"><?= $v ?></a>
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

        <input type="text" name="search" placeholder="Search user or package" value="<?= htmlspecialchars($search) ?>">
        <button>Apply</button>
    </form>
</div>

<div class="table-box">
<table>
<thead>
<tr>
    <th>User</th>
    <th>Package</th>
    <th>Check-in</th>
    <th>Check-out</th>
    <th>Nights</th>
    <th>People</th>
    <th>Total</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php foreach ($bookings as $b): ?>
<tr>
    <td><?= htmlspecialchars($b['full_name']) ?></td>
    <td><?= htmlspecialchars($b['title']) ?></td>
    <td><?= $b['check_in'] ?></td>
    <td><?= $b['check_out'] ?></td>
    <td><?= $b['nights'] ?></td>
    <td><?= $b['number_of_people'] ?></td>
    <td>$<?= number_format($b['total_amount'],2) ?></td>
    <td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
    <td>
        <?php if ($b['status']==='pending'): ?>
        <div class="actions">
            <button class="btn-action btn-approve"
                onclick="location.href='admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=confirmed'">
                ✔ Approve
            </button>
            <button class="btn-action btn-cancel"
                onclick="openCancelModal('admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=cancelled')">
                ✖ Cancel
            </button>
        </div>
        <?php else: ?> —
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</div>

<!-- CANCEL MODAL -->
<div class="modal-bg" id="cancelModal">
    <div class="modal">
        <h3>Cancel Booking?</h3>
        <p>This action cannot be undone.</p>
        <div class="modal-actions">
            <button onclick="closeCancelModal()">No</button>
            <a id="cancelLink"><button class="btn-cancel">Yes, Cancel</button></a>
        </div>
    </div>
</div>

<script>
function openCancelModal(url){
    document.getElementById('cancelLink').href = url;
    document.getElementById('cancelModal').classList.add('show');
}
function closeCancelModal(){
    document.getElementById('cancelModal').classList.remove('show');
}
</script>

</body>
</html>
