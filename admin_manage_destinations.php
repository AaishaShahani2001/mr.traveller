<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
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
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }

body {
    background: #f5f6fa;
    padding: 22px;
}

/* Header */
.header {
    max-width: 1200px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 15px;
}
.header h2 { margin: 0; }
.back-btn {
    background: #444;
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
}

/* Controls */
.controls {
    max-width: 1200px;
    margin: auto;
    background: white;
    padding: 14px;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}
.controls form {
    display: grid;
    grid-template-columns: 1fr 200px auto auto;
    gap: 10px;
}
.controls input, .controls select {
    padding: 11px;
    border-radius: 10px;
    border: 1px solid #ccc;
}
.controls button, .controls a {
    padding: 11px 16px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}
.controls button {
    background: #007bff;
    color: white;
}
.controls a {
    background: #eef3ff;
    color: #0b3d91;
    text-decoration: none;
    text-align: center;
}

/* Table */
.table-box {
    max-width: 1200px;
    margin: 18px auto;
    background: white;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    overflow: hidden;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    background: #007bff;
    color: white;
    padding: 14px;
    text-align: left;
}
td {
    padding: 14px;
    border-bottom: 1px solid #eee;
}
tr:hover td { background: #f2f6ff; }

img {
    width: 90px;
    height: 65px;
    object-fit: cover;
    border-radius: 8px;
}

/* Actions */
.actions {
    display: flex;
    gap: 10px;
}
.actions a, .actions button {
    padding: 8px 14px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    color: white;
    font-size: 13px;
}
.edit { background: #28a745; }
.delete { background: #dc3545; }

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}
.pagination a {
    padding: 9px 12px;
    border-radius: 8px;
    text-decoration: none;
    background: white;
    color: #333;
    font-weight: bold;
    border: 1px solid #ddd;
}
.pagination a.active {
    background: #007bff;
    color: white;
}

/* Toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 14px 22px;
    border-radius: 12px;
    font-weight: bold;
    box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    opacity: 0;
    transform: translateY(-20px);
    transition: .5s;
    z-index: 9999;
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.delete { background: #dc3545; }

/* Modal */
.modal-bg {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: none;
    justify-content: center;
    align-items: center;
}
.modal-bg.show { display: flex; }
.modal {
    background: white;
    padding: 22px;
    border-radius: 14px;
    max-width: 420px;
    width: 100%;
}
.modal button {
    padding: 10px 16px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}
.cancel { background: #e9ecef; }
.confirm { background: #dc3545; color: white; }

/* Responsive */
@media (max-width: 900px) {
    .controls form { grid-template-columns: 1fr; }
    table thead { display: none; }
    table, tr, td { display: block; }
    td { padding: 10px 14px; }
}
</style>
</head>

<body>

<div class="header">
    <h2>Manage Destinations</h2>
    <a href="admin_dashboard.php" class="back-btn">← Dashboard</a>
</div>

<div class="controls">
<form>
    <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($q) ?>">
    <select name="country">
        <option value="">All Countries</option>
        <?php foreach ($countries as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $countryFilter === $c ? 'selected' : '' ?>>
                <?= htmlspecialchars($c) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button>Search</button>
    <a href="admin_manage_destinations.php">Reset</a>
</form>
</div>

<div class="table-box">
<table>
<thead>
<tr>
    <th>Image</th>
    <th>Title</th>
    <th>Location</th>
    <th>Price</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($destinations as $d): ?>
<tr>
    <td><img src="uploads/<?= htmlspecialchars($d['image']) ?>"></td>
    <td><?= htmlspecialchars($d['title']) ?></td>
    <td><?= htmlspecialchars($d['country']) ?> - <?= htmlspecialchars($d['city']) ?></td>
    <td>$<?= number_format($d['price'],2) ?></td>
    <td>
        <div class="actions">
            <a class="edit" href="admin_edit_destination.php?id=<?= $d['dest_id'] ?>">Edit</a>
            <button class="delete" onclick="openModal('admin_delete_destination.php?id=<?= $d['dest_id'] ?>')">Delete</button>
        </div>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="pagination">
<?php for ($i=1; $i<=$totalPages; $i++): ?>
<a class="<?= $i==$page?'active':'' ?>" href="?<?= q(['page'=>$i]) ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

<!-- Delete Modal -->
<div class="modal-bg" id="modal">
    <div class="modal">
        <h3>Confirm Delete</h3>
        <p>This action cannot be undone.</p>
        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button class="cancel" onclick="closeModal()">Cancel</button>
            <a id="deleteLink"><button class="confirm">Delete</button></a>
        </div>
    </div>
</div>

<!-- Toast -->
<?php if ($msg): ?>
<div class="toast <?= $msg==='deleted'?'delete':'' ?>" id="toast">
    <?= $msg==='updated' ? 'Destination updated successfully ✔' : 'Destination deleted successfully 🗑' ?>
</div>
<script>
setTimeout(()=>document.getElementById('toast').classList.add('show'),200);
setTimeout(()=>document.getElementById('toast').classList.remove('show'),3200);
</script>
<?php endif; ?>

<script>
function openModal(url){
    document.getElementById('deleteLink').href = url;
    document.getElementById('modal').classList.add('show');
}
function closeModal(){
    document.getElementById('modal').classList.remove('show');
}
</script>

</body>
</html>
