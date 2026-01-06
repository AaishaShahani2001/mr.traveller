<?php 
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* ---------- Toast message ---------- */
$msg = $_GET['msg'] ?? "";

/* ---------- Search & Filter ---------- */
$q = trim($_GET['q'] ?? "");
$countryFilter = trim($_GET['country'] ?? "");

/* ---------- Pagination ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

/* ---------- Country list ---------- */
$countries = $conn->query("
    SELECT DISTINCT country 
    FROM destinations 
    WHERE country IS NOT NULL AND country <> ''
    ORDER BY country
")->fetchAll(PDO::FETCH_COLUMN);

/* ---------- WHERE clause ---------- */
$where = [];
$params = [];

if ($q !== "") {
    $where[] = "(title LIKE :q OR country LIKE :q OR city LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($countryFilter !== "") {
    $where[] = "country = :country";
    $params[':country'] = $countryFilter;
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ---------- Count ---------- */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM destinations $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ---------- Fetch data ---------- */
$sql = "
    SELECT * FROM destinations
    $whereSql
    ORDER BY dest_id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q($arr = []) {
    return http_build_query(array_merge($_GET, $arr));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Destinations | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box;font-family:"Segoe UI",Arial,sans-serif}
body{margin:0;background:#f5f6fa}

/* ===== TOP BAR ===== */
.topbar{
    display:none;
    background:#1f2937;
    color:white;
    padding:14px 18px;
    align-items:center;
    justify-content:space-between;
    position:sticky;
    top:0;
    z-index:3000;
}
.menu-btn{font-size:22px;cursor:pointer}

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
    transition:transform .3s ease;
    z-index:2000;
}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{
    display:block;padding:14px 22px;
    color:#e5e7eb;text-decoration:none;
}
.sidebar a:hover,.sidebar a.active{
    background:#2563eb;color:white
}

/* ===== MAIN ===== */
.main{margin-left:250px;padding:24px;width:100%}

/* ===== CONTROLS ===== */
.controls{
    max-width:1200px;margin:auto;
    background:white;padding:14px;
    border-radius:14px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.controls form{
    display:grid;
    grid-template-columns:1fr 200px auto auto;
    gap:10px;
}
.controls input,.controls select{
    padding:11px;border-radius:10px;border:1px solid #ccc;
}
.controls button{
    padding:11px 16px;border-radius:10px;
    background:#2563eb;color:white;border:none;cursor:pointer;
}
.controls a{
    padding:11px 16px;border-radius:10px;
    background:#eef3ff;color:#0b3d91;
    text-decoration:none;text-align:center;
}

/* ===== TABLE ===== */
.table-box{
    max-width:1200px;margin:18px auto;
    background:white;border-radius:14px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    overflow:hidden;
}
table{width:100%;border-collapse:collapse}
th{background:#2563eb;color:white;padding:14px}
td{padding:14px;border-bottom:1px solid #eee}
tr:hover td{background:#f2f6ff}
img{width:90px;height:65px;object-fit:cover;border-radius:8px}

/* ===== ACTIONS ===== */
.actions{display:flex;gap:8px;flex-wrap:wrap}
.btn-action{
    padding:8px 14px;border-radius:999px;
    border:none;font-size:13px;font-weight:600;
    cursor:pointer;
}
.btn-edit{background:#22c55e;color:white}
.btn-delete{background:#ef4444;color:white}

/* ===== PAGINATION ===== */
.pagination{
    max-width:1200px;margin:30px auto;
    display:flex;justify-content:center;gap:10px;flex-wrap:wrap;
}
.pagination a,.pagination span{
    min-width:42px;height:42px;padding:0 16px;
    display:flex;align-items:center;justify-content:center;
    border-radius:999px;font-weight:600;
    border:1px solid #e5e7eb;background:white;
}
.pagination a.active{background:#2563eb;color:white}
.pagination .disabled{opacity:.4;pointer-events:none}

/* ===== MODAL ===== */
.modal-bg{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999; 
}
.modal-bg.show{display:flex}
.modal{
    background:white;
    padding:24px;
    border-radius:16px;
    max-width:420px;width:100%;
}

/* ===== TOAST (FIXED) ===== */
.toast{
    position:fixed;
    top:20px;
    right:20px;
    padding:14px 22px;
    border-radius:12px;
    color:white;
    font-weight:bold;
    opacity:0;
    transform:translateY(-20px);
    transition:.4s;
    z-index:10000; 
}
.toast.show{opacity:1;transform:translateY(0)}
.toast.delete{background:#dc3545}
.toast.update{background:#28a745}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .topbar{display:flex}
    .sidebar{transform:translateX(-100%)}
    .sidebar.show{transform:translateX(0)}
    .main{margin-left:0}
    .controls form{grid-template-columns:1fr}
}
</style>
</head>

<body>

<div class="topbar">
    <span class="menu-btn" onclick="toggleSidebar()">☰</span>
    <strong>Admin Panel</strong>
</div>

<div class="layout">

<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a class="active" href="admin_manage_destinations.php">📍 Destinations</a>
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

<div class="controls">
<form>
    <input name="q" placeholder="Search..." value="<?= htmlspecialchars($q) ?>">
    <select name="country">
        <option value="">All Countries</option>
        <?php foreach ($countries as $c): ?>
            <option <?= $countryFilter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
        <?php endforeach ?>
    </select>
    <button>Search</button>
    <a href="admin_manage_destinations.php">Reset</a>
</form>
</div>

<div class="table-box">
<table>
<thead>
<tr><th>Image</th><th>Title</th><th>Location</th><th>Price</th><th>Actions</th></tr>
</thead>
<tbody>
<?php foreach ($destinations as $d): ?>
<tr>
<td><img src="uploads/<?= htmlspecialchars($d['image']) ?>"></td>
<td><?= htmlspecialchars($d['title']) ?></td>
<td><?= htmlspecialchars($d['country']) ?> - <?= htmlspecialchars($d['city']) ?></td>
<td>$<?= number_format($d['price']) ?></td>
<td class="actions">
<a class="btn-action btn-edit" href="admin_edit_destination.php?id=<?= $d['dest_id'] ?>">Edit</a>
<button class="btn-action btn-delete" onclick="openDeleteModal('admin_delete_destination.php?id=<?= $d['dest_id'] ?>')">Delete</button>
</td>
</tr>
<?php endforeach ?>
</tbody>
</table>
</div>

<div class="pagination">
<?php if ($page > 1): ?>
<a href="?<?= q(['page'=>$page-1]) ?>">‹</a>
<?php else: ?><span class="disabled">‹</span><?php endif; ?>

<?php for ($i=max(1,$page-2); $i<=min($totalPages,$page+2); $i++): ?>
<a class="<?= $i==$page?'active':'' ?>" href="?<?= q(['page'=>$i]) ?>"><?= $i ?></a>
<?php endfor; ?>

<?php if ($page < $totalPages): ?>
<a href="?<?= q(['page'=>$page+1]) ?>">›</a>
<?php else: ?><span class="disabled">›</span><?php endif; ?>
</div>

</div>
</div>

<!-- DELETE MODAL -->
<div class="modal-bg" id="deleteModal">
    <div class="modal">
        <h3>Delete Destination?</h3>
        <p>This action cannot be undone.</p>
        <div style="text-align:right">
            <button onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteLink"><button class="btn-delete">Delete</button></a>
        </div>
    </div>
</div>

<!-- TOAST -->
<?php if ($msg): ?>
<div class="toast <?= $msg==='deleted'?'delete':'update' ?>" id="toast">
<?= $msg==='deleted'?'Destination deleted successfully 🗑':'Destination updated successfully ✔' ?>
</div>
<script>
const toastEl = document.getElementById('toast');
setTimeout(()=>toastEl.classList.add('show'),200);
setTimeout(()=>toastEl.classList.remove('show'),3200);
</script>
<?php endif; ?>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
}
function openDeleteModal(url){
    document.getElementById('deleteLink').href = url;
    document.getElementById('deleteModal').classList.add('show');
}
function closeDeleteModal(){
    document.getElementById('deleteModal').classList.remove('show');
}
</script>

</body>
</html>
