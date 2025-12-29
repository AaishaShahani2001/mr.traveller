<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* ---------- Filters ---------- */
$destFilter = $_GET['dest'] ?? '';
$typeFilter = $_GET['type'] ?? '';

/* ---------- Toast ---------- */
$msg = $_GET['msg'] ?? "";

/* ---------- Destinations for filter ---------- */
$destinations = $conn->query("
    SELECT dest_id, title 
    FROM destinations 
    ORDER BY title
")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- WHERE ---------- */
$where = "WHERE 1";
$params = [];

if ($destFilter) {
    $where .= " AND f.dest_id = ?";
    $params[] = $destFilter;
}

if ($typeFilter) {
    $where .= " AND f.transport_type = ?";
    $params[] = $typeFilter;
}

/* ---------- Fetch facilities ---------- */
$stmt = $conn->prepare("
    SELECT 
        f.facility_id,
        f.transport_type,
        f.provider_name,
        f.price,
        f.duration,
        d.title AS destination
    FROM travel_facilities f
    JOIN destinations d ON f.dest_id = d.dest_id
    $where
    ORDER BY f.facility_id DESC
");
$stmt->execute($params);
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Travel Facilities | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box;font-family:"Segoe UI",Arial}
body{margin:0;background:#f5f7ff}

/* Sidebar */
.sidebar{
    width:250px;background:#1f2937;color:#fff;
    padding-top:30px;position:fixed;height:100%;
}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{
    display:block;padding:14px 22px;
    color:#e5e7eb;text-decoration:none
}
.sidebar a:hover,.sidebar a.active{
    background:#2563eb;color:#fff
}

/* Content */
.content{
    margin-left:250px;
    padding:40px;
}

/* Toast (NEW) */
.toast{
    position:fixed;
    top:20px;
    right:20px;
    padding:14px 22px;
    border-radius:12px;
    font-weight:600;
    color:#fff;
    opacity:0;
    transform:translateY(-20px);
    transition:.4s ease;
    z-index:9999;
}
.toast.show{opacity:1;transform:translateY(0)}
.toast.success{background:#16a34a}
.toast.error{background:#dc2626}

/* Filters */
.filters{
    background:#fff;
    padding:16px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    margin-bottom:20px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
.filters select,.filters button{
    padding:10px 14px;
    border-radius:10px;
    border:1px solid #ccc;
}
.filters button{
    background:#2563eb;color:#fff;
    border:none;font-weight:600;
}

/* ===== DESKTOP TABLE ===== */
.table-wrap{overflow-x:auto}
table{
    width:100%;
    min-width:750px;
    background:#fff;
    border-collapse:collapse;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.12)
}
th,td{
    padding:14px;
    border-bottom:1px solid #eee;
    text-align:center;
    white-space:nowrap;
}
th{background:#2563eb;color:#fff}

/* ===== MOBILE CARDS ===== */
.card-list{display:none}
.facility-card{
    background:#fff;
    padding:18px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    margin-bottom:16px;
}
.facility-card h3{
    margin:0 0 8px;
    font-size:18px;
}
.facility-card p{
    margin:6px 0;
    font-size:14px;
}
.facility-card .actions{
    margin-top:12px;
    display:flex;
    gap:10px;
}

/* Actions */
.actions{display:flex;gap:8px;justify-content:center}
.btn{
    padding:8px 14px;
    border-radius:999px;
    font-weight:700;
    border:none;
    cursor:pointer;
}
.edit{background:#e8f0ff;color:#005fcc}
.delete{background:#fdecea;color:#c0392b}

/* Modal */
.modal{
    position:fixed;inset:0;
    background:rgba(0,0,0,.6);
    display:none;align-items:center;justify-content:center
}
.modal.show{display:flex}
.modal-box{
    background:#fff;
    padding:24px;
    border-radius:16px;
    max-width:420px;
    text-align:center
}
.modal-actions{
    margin-top:18px;
    display:flex;gap:12px;justify-content:center
}
.m-btn{
    padding:10px 18px;
    border-radius:999px;
    border:none;font-weight:700
}
.m-cancel{background:#e5e7eb}
.m-delete{background:#dc2626;color:#fff;text-decoration:none}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .sidebar{position:relative;width:100%;height:auto}
    .content{margin-left:0;padding:24px}
    .table-wrap{display:none}
    .card-list{display:block}
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a class="active" href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="content">
<h1>Manage Travel Facilities</h1>

<?php if ($msg): ?>
<div class="toast <?= $msg==='in_use'?'error':'success' ?>" id="toast">
<?php
if ($msg==='deleted') echo "Travel facility deleted successfully 🗑️";
elseif ($msg==='updated') echo "Travel facility updated successfully ✅";
elseif ($msg==='in_use') echo "Cannot delete – this transport is used in bookings ❌";
?>
</div>
<script>
const toast=document.getElementById("toast");
setTimeout(()=>toast.classList.add("show"),200);
setTimeout(()=>toast.classList.remove("show"),3200);
</script>
<?php endif; ?>

<!-- Filters -->
<form class="filters">
    <select name="dest">
        <option value="">All Destinations</option>
        <?php foreach($destinations as $d): ?>
            <option value="<?= $d['dest_id'] ?>" <?= $destFilter==$d['dest_id']?'selected':'' ?>>
                <?= htmlspecialchars($d['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="type">
        <option value="">All Transport Types</option>
        <?php foreach(['Bus','Jeep','Taxi','Boat'] as $t): ?>
            <option value="<?= $t ?>" <?= $typeFilter==$t?'selected':'' ?>><?= $t ?></option>
        <?php endforeach; ?>
    </select>

    <button>Apply</button>
</form>

<?php if(!$facilities): ?>
<p>No travel facilities found.</p>
<?php else: ?>

<!-- ===== DESKTOP TABLE ===== -->
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>Destination</th>
    <th>Transport</th>
    <th>Provider</th>
    <th>Duration</th>
    <th>Price</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($facilities as $f): ?>
<tr>
    <td><?= htmlspecialchars($f['destination']) ?></td>
    <td><?= htmlspecialchars($f['transport_type']) ?></td>
    <td><?= htmlspecialchars($f['provider_name'] ?: '—') ?></td>
    <td><?= htmlspecialchars($f['duration'] ?: '—') ?></td>
    <td>$<?= number_format($f['price'],2) ?></td>
    <td>
        <div class="actions">
            <a class="btn edit" href="edit_travel_facility.php?id=<?= $f['facility_id'] ?>">✏ Edit</a>
            <button class="btn delete" onclick="openDelete(<?= $f['facility_id'] ?>)">❌ Delete</button>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ===== MOBILE CARDS ===== -->
<div class="card-list">
<?php foreach($facilities as $f): ?>
<div class="facility-card">
    <h3><?= htmlspecialchars($f['destination']) ?></h3>
    <p><strong>Transport:</strong> <?= htmlspecialchars($f['transport_type']) ?></p>
    <p><strong>Provider:</strong> <?= htmlspecialchars($f['provider_name'] ?: '—') ?></p>
    <p><strong>Duration:</strong> <?= htmlspecialchars($f['duration'] ?: '—') ?></p>
    <p><strong>Price:</strong> $<?= number_format($f['price'],2) ?></p>
    <div class="actions">
        <a class="btn edit" href="edit_travel_facility.php?id=<?= $f['facility_id'] ?>">✏ Edit</a>
        <button class="btn delete" onclick="openDelete(<?= $f['facility_id'] ?>)">❌ Delete</button>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-box">
        <h3>Delete Travel Facility?</h3>
        <p>This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="m-btn m-cancel" onclick="closeDelete()">Cancel</button>
            <a id="deleteLink" class="m-btn m-delete">Yes, Delete</a>
        </div>
    </div>
</div>

<script>
function openDelete(id){
    document.getElementById('deleteLink').href =
        'delete_travel_facility.php?id=' + id;
    document.getElementById('deleteModal').classList.add('show');
}
function closeDelete(){
    document.getElementById('deleteModal').classList.remove('show');
}
</script>

</body>
</html>
