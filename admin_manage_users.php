<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* ---------- Search ---------- */
$q = trim($_GET['q'] ?? "");

/* ---------- Pagination ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

/* ---------- WHERE ---------- */
$where = "";
$params = [];

if ($q !== "") {
    $where = "WHERE full_name LIKE :q OR email LIKE :q OR role LIKE :q";
    $params[':q'] = "%$q%";
}

/* ---------- Count ---------- */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ---------- Fetch users ---------- */
$sql = "
    SELECT * FROM users
    $where
    ORDER BY user_id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q($arr = []) {
    return http_build_query(array_merge($_GET, $arr));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Admin</title>
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
    color:#fff;
    align-items:center;
    padding:0 16px;
    z-index:1200;
}
.hamburger{
    font-size:22px;
    cursor:pointer;
    margin-right:12px;
}
.topbar-title{font-weight:700}

/* ===== OVERLAY ===== */
.overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:1000;
}
.overlay.show{display:block}

/* ===== LAYOUT ===== */
.layout{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    height:100%;
    transition:.3s ease;
    z-index:1100;
}
.sidebar.hide{transform:translateX(-100%)}
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

/* Header */
.header{
    max-width:1100px;
    margin:auto;
    margin-bottom:15px;
}

/* Controls */
.controls{
    max-width:1100px;
    margin:auto;
    background:white;
    padding:14px;
    border-radius:14px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.controls form{
    display:grid;
    grid-template-columns:1fr auto auto;
    gap:10px;
}
.controls input{
    padding:11px;
    border-radius:10px;
    border:1px solid #ccc;
}
.controls button,.controls a{
    padding:11px 16px;
    border-radius:10px;
    border:none;
    font-weight:bold;
    cursor:pointer;
}
.controls button{background:#007bff;color:white}
.controls a{
    background:#eef3ff;color:#0b3d91;
    text-decoration:none;text-align:center;
}

/* ===== DESKTOP TABLE ===== */
.table-wrap{
    max-width:1100px;
    margin:18px auto;
    overflow-x:auto;
}
table{
    width:100%;
    min-width:650px;
    background:white;
    border-collapse:collapse;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
th{
    background:#34495e;color:white;
    padding:14px;text-align:left;
}
td{
    padding:14px;
    border-bottom:1px solid #eee;
}
tr:hover td{background:#f2f6ff}

/* Role badge */
.role{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}
.role.admin{background:#ffe5e5;color:#c0392b}
.role.user{background:#e9f9ee;color:#2e7d32}

/* ===== MOBILE CARDS ===== */
.card-list{display:none}
.user-card{
    background:white;
    padding:18px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    margin-bottom:16px;
}
.user-card h3{
    margin:0 0 6px;
    font-size:18px;
}
.user-card p{
    margin:6px 0;
    font-size:14px;
}

/* Pagination */
.pagination{
    display:flex;
    justify-content:center;
    gap:8px;
    margin:20px 0;
    flex-wrap:wrap;
}
.pagination a{
    padding:9px 12px;
    border-radius:8px;
    text-decoration:none;
    background:white;
    color:#333;
    font-weight:bold;
    border:1px solid #ddd;
}
.pagination a.active{
    background:#007bff;
    color:white;
}

/* ===== MOBILE  ===== */
@media(max-width:900px){
    .topbar{display:flex}
    .sidebar{transform:translateX(-100%)}
    .sidebar.show{transform:translateX(0)}
    .main{
        margin-left:0;
        padding:24px;
        padding-top:80px;
    }
    .controls form{grid-template-columns:1fr}
    .table-wrap{display:none}
    .card-list{display:block}
}
</style>
</head>

<body>

<!-- ===== MOBILE TOP BAR ===== -->
<div class="topbar">
    <span class="hamburger" onclick="toggleMenu()">☰</span>
    <span class="topbar-title">Manage Users</span>
</div>
<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div class="layout">

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a class="active" href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- MAIN -->
<div class="main">

<div class="header">
    <h2>Manage Users</h2>
</div>

<div class="controls">
<form>
    <input type="text" name="q" placeholder="Search users..." value="<?= htmlspecialchars($q) ?>">
    <button>Search</button>
    <a href="admin_manage_users.php">Reset</a>
</form>
</div>

<?php if(!$users): ?>
<p>No users found.</p>
<?php else: ?>

<!-- DESKTOP TABLE -->
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Created At</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= htmlspecialchars($u['full_name']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td>
        <span class="role <?= $u['role']==='admin'?'admin':'user' ?>">
            <?= htmlspecialchars($u['role']) ?>
        </span>
    </td>
    <td><?= htmlspecialchars($u['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- MOBILE CARDS -->
<div class="card-list">
<?php foreach ($users as $u): ?>
<div class="user-card">
    <h3><?= htmlspecialchars($u['full_name']) ?></h3>
    <p><strong>Email:</strong> <?= htmlspecialchars($u['email']) ?></p>
    <p><strong>Role:</strong>
        <span class="role <?= $u['role']==='admin'?'admin':'user' ?>">
            <?= htmlspecialchars($u['role']) ?>
        </span>
    </p>
    <p><strong>Joined:</strong> <?= htmlspecialchars($u['created_at']) ?></p>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<div class="pagination">
<?php for ($i=1;$i<=$totalPages;$i++): ?>
    <a class="<?= $i==$page?'active':'' ?>" href="?<?= q(['page'=>$i]) ?>">
        <?= $i ?>
    </a>
<?php endfor; ?>
</div>

</div>
</div>

<script>
function toggleMenu(){
    document.getElementById("sidebar").classList.toggle("show");
    document.getElementById("overlay").classList.toggle("show");
}
</script>

</body>
</html>
