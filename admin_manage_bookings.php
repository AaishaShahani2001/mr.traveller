<?php
session_start();
require "config.php";

/* ---------- Admin only ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ---------- Toast message ---------- */
$msg = $_GET['msg'] ?? "";

/* ---------- Filters ---------- */
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

/* ---------- Pagination ---------- */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

/* ---------- Base Query ---------- */
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

/* ---------- Count ---------- */
$countSql = "
SELECT COUNT(*)
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN destinations d ON b.dest_id = d.dest_id
$where
";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* ---------- Fetch Data ---------- */
$sql = "
SELECT b.*, u.full_name, d.title
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
}
.header h2 { margin: 0; }
.back-btn {
    background: #444;
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
}

/* Filters */
.filters {
    max-width: 1200px;
    margin: 14px auto;
    background: white;
    padding: 14px;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
}

.status-links a {
    margin-right: 12px;
    font-weight: bold;
    text-decoration: none;
    color: #555;
}
.status-links a.active {
    color: #007bff;
}

.search-box input {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ccc;
}
.search-box button {
    padding: 10px 16px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

/* Table */
.table-box {
    max-width: 1200px;
    margin: auto;
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
    background: #8e44ad;
    color: white;
    padding: 14px;
    text-align: left;
}
td {
    padding: 14px;
    border-bottom: 1px solid #eee;
}
tr:hover td { background: #f2f6ff; }

/* Status */
.status {
    font-weight: bold;
    padding: 6px 12px;
    border-radius: 20px;
    display: inline-block;
}
.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#e9f9ee; color:#2e7d32; }
.cancelled { background:#fdecea; color:#c0392b; }

/* Actions */
.actions button {
    padding: 8px 14px;
    border-radius: 20px;
    border: none;
    color: white;
    font-size: 13px;
    cursor: pointer;
}
.approve { background:#27ae60; }
.cancel { background:#c0392b; }

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
.cancel-btn { background:#e9ecef; }
.confirm-btn { background:#dc3545; color:white; }

/* Responsive */
@media (max-width: 900px) {
    .filters { grid-template-columns: 1fr; }
    table thead { display: none; }
    table, tr, td { display: block; }
    td { padding: 10px 14px; }
    td::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
}
</style>
</head>

<body>

<div class="header">
    <h2>Manage Bookings</h2>
    <a href="admin_dashboard.php" class="back-btn">← Dashboard</a>
</div>

<div class="filters">
    <div class="status-links">
        <?php
        $statuses = ['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','cancelled'=>'Cancelled'];
        foreach ($statuses as $k=>$v):
        ?>
            <a class="<?= $status===$k?'active':'' ?>" href="?<?= q(['status'=>$k,'page'=>1]) ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <form class="search-box">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
        <input type="text" name="search" placeholder="Search user or package" value="<?= htmlspecialchars($search) ?>">
        <button>Search</button>
    </form>
</div>

<div class="table-box">
<table>
<thead>
<tr>
    <th>User</th>
    <th>Package</th>
    <th>Date</th>
    <th>People</th>
    <th>Total</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php if (!$bookings): ?>
<tr><td colspan="7" style="text-align:center;">No bookings found</td></tr>
<?php endif; ?>

<?php foreach ($bookings as $b): ?>
<tr>
    <td data-label="User"><?= htmlspecialchars($b['full_name']) ?></td>
    <td data-label="Package"><?= htmlspecialchars($b['title']) ?></td>
    <td data-label="Date"><?= $b['travel_date'] ?></td>
    <td data-label="People"><?= $b['number_of_people'] ?></td>
    <td data-label="Total">$<?= number_format($b['total_amount'],2) ?></td>
    <td data-label="Status">
        <span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
    </td>
    <td data-label="Action">
        <?php if ($b['status']==='pending'): ?>
            <button class="approve"
                onclick="openModal('admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=confirmed')">
                Approve
            </button>
            <button class="cancel"
                onclick="openModal('admin_update_booking.php?id=<?= $b['booking_id'] ?>&status=cancelled')">
                Cancel
            </button>
        <?php else: ?>—
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="pagination">
<?php for ($i=1;$i<=$totalPages;$i++): ?>
<a class="<?= $i==$page?'active':'' ?>" href="?<?= q(['page'=>$i]) ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

<!-- Modal -->
<div class="modal-bg" id="modal">
    <div class="modal">
        <h3>Confirm Action</h3>
        <p>This action cannot be undone.</p>
        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button class="cancel-btn" onclick="closeModal()">Cancel</button>
            <a id="actionLink"><button class="confirm-btn">Confirm</button></a>
        </div>
    </div>
</div>

<!-- Toast -->
<?php if ($msg==='updated'): ?>
<div class="toast" id="toast">Booking status updated successfully ✔</div>
<script>
setTimeout(()=>document.getElementById('toast').classList.add('show'),200);
setTimeout(()=>document.getElementById('toast').classList.remove('show'),3200);
</script>
<?php endif; ?>

<script>
function openModal(url){
    document.getElementById('actionLink').href = url;
    document.getElementById('modal').classList.add('show');
}
function closeModal(){
    document.getElementById('modal').classList.remove('show');
}
</script>

</body>
</html>
