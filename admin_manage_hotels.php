<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* ===== PAGINATION ===== */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

/* Count total hotels */
$countStmt = $conn->query("SELECT COUNT(*) FROM hotels");
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;


/* Fetch hotels with destination */
$stmt = $conn->prepare("
    SELECT 
        h.hotel_id,
        h.name,
        h.type,
        h.price_per_night,
        h.rating,
        h.amenities,
        h.image,
        d.title AS destination
    FROM hotels h
    JOIN destinations d ON h.dest_id = d.dest_id
    ORDER BY h.hotel_id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Hotels | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial; }

body {
    margin:0;
    background:#f5f7ff;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    inset:0 auto 0 0;
}

.sidebar h2 {
    text-align:center;
    margin-bottom:30px;
}

.sidebar a {
    display:block;
    padding:14px 22px;
    color:#e5e7eb;
    text-decoration:none;
    font-weight:500;
}

.sidebar a:hover,
.sidebar a.active {
    background:#2563eb;
    color:white;
}

/* ===== CONTENT ===== */
.content {
    margin-left:250px;
    padding:40px;
    min-height:100vh;
}

h1 {
    margin-bottom:20px;
}

/* ===== TABLE ===== */
table {
    width:100%;
    background:white;
    border-collapse:collapse;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
}

th, td {
    padding:14px;
    border-bottom:1px solid #eee;
    text-align:center;
    font-size:14px;
}

th {
    background:#2563eb;
    color:white;
}

/* Image */
.hotel-img {
    width:90px;
    height:60px;
    object-fit:cover;
    border-radius:8px;
}

/* ===== AMENITIES ===== */
.amenities {
    max-width:220px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    margin:auto;
}

.view-more {
    color:#2563eb;
    font-weight:600;
    cursor:pointer;
    position:relative;
    margin-left:6px;
}

/* Tooltip */
.view-more:hover::after {
    content: attr(data-full);
    position:absolute;
    left:50%;
    top:120%;
    transform:translateX(-50%);
    background:#111827;
    color:white;
    padding:10px 14px;
    border-radius:10px;
    width:260px;
    white-space:normal;
    text-align:left;
    z-index:999;
    box-shadow:0 15px 40px rgba(0,0,0,.35);
    font-size:13px;
}

/* ===== ACTION BUTTONS ===== */
.actions {
    display:flex;
    gap:8px;
    justify-content:center;
}

.btn {
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:bold;
    text-decoration:none;
    border:none;
    cursor:pointer;
}

.edit-btn {
    background:#e8f0ff;
    color:#005fcc;
}

.edit-btn:hover {
    background:#d6e4ff;
}

.delete-btn {
    background:#fdecea;
    color:#c0392b;
}

.delete-btn:hover {
    background:#fadbd8;
}

/* ===== EMPTY ===== */
.empty {
    background:white;
    padding:40px;
    border-radius:16px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
}

/* ===== MOBILE CARD VIEW ===== */
.card-list { display:none; }

.hotel-card{
    background:#fff;
    border-radius:16px;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
    padding:16px;
    margin-bottom:16px;
}

.card-top{
    display:flex;
    gap:12px;
    align-items:center;
}

.card-img{
    width:90px;
    height:70px;
    border-radius:12px;
    object-fit:cover;
    flex:0 0 auto;
    background:#eef2ff;
}

.card-title{
    margin:0;
    font-size:16px;
    font-weight:800;
    color:#111827;
}

.card-sub{
    margin:4px 0 0;
    font-size:13px;
    color:#6b7280;
}

.card-meta{
    margin-top:12px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    font-size:13px;
    color:#374151;
}

.meta-item{
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:12px;
    padding:10px;
}

.meta-item b{
    display:block;
    font-size:12px;
    color:#6b7280;
    margin-bottom:4px;
}

.card-amenities{
    margin-top:12px;
    background:#f8fafc;
    border:1px solid #eef2f7;
    border-radius:12px;
    padding:10px;
    font-size:13px;
    color:#374151;
    line-height:1.5;
}

.card-actions{
    margin-top:14px;
    display:flex;
    gap:10px;
}
.card-actions a, .card-actions button{
    flex:1;
    text-align:center;
    justify-content:center;
    display:inline-flex;
    align-items:center;
}

/* ===== PAGINATION ===== */
.pagination{
    margin-top:25px;
    display:flex;
    justify-content:center;
    gap:8px;
    flex-wrap:wrap;
}
.pagination a{
    padding:10px 14px;
    border-radius:10px;
    background:white;
    border:1px solid #ddd;
    text-decoration:none;
    font-weight:bold;
    color:#333;
}
.pagination a.active{
    background:#2563eb;
    color:white;
}

/* ===== DELETE MODAL  ===== */
.modal-bg{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:18px;
}
.modal-bg.show{display:flex}

.modal{
    background:#fff;
    width:100%;
    max-width:420px;
    border-radius:16px;
    padding:22px;
    box-shadow:0 20px 60px rgba(0,0,0,.35);
    text-align:center;
}
.modal h3{margin:0 0 8px}
.modal p{margin:0;color:#555}

.modal-actions{
    margin-top:18px;
    display:flex;
    gap:12px;
    justify-content:center;
}
.modal-actions button, .modal-actions a{
    padding:10px 18px;
    border-radius:999px;
    border:none;
    font-weight:800;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
}
.btn-cancel{background:#e5e7eb}
.btn-confirm{background:#dc2626;color:#fff;text-decoration:none}

/* ===== RESPONSIVE ===== */
@media(max-width:1000px){
    .sidebar {
        position:relative;
        width:100%;
    }
    .content {
        margin-left:0;
        padding:24px;
    }

    /* mobile: cards instead of table */
    table{display:none}
    .card-list{display:block}
}
</style>
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a class="active" href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- ===== CONTENT ===== -->
<div class="content">
<h1>Manage Hotels</h1>

<?php if (!$hotels): ?>
<div class="empty">
    <h3>No hotels added yet</h3>
    <p>Add accommodations to manage them here.</p>
</div>
<?php else: ?>

<!-- ===== DESKTOP TABLE ===== -->
<table>
<thead>
<tr>
    <th>Image</th>
    <th>Hotel Name</th>
    <th>Destination</th>
    <th>Type</th>
    <th>Amenities</th>
    <th>Price / Night</th>
    <th>Rating</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach ($hotels as $h): ?>
<tr>
<td>
    <?php if (!empty($h['image'])): ?>
        <img src="uploads/<?= htmlspecialchars($h['image']) ?>" class="hotel-img">
    <?php else: ?>
        —
    <?php endif; ?>
</td>

<td><?= htmlspecialchars($h['name']) ?></td>
<td><?= htmlspecialchars($h['destination']) ?></td>
<td><?= htmlspecialchars($h['type']) ?></td>

<td>
<?php
$full = trim($h['amenities'] ?? '');
$short = mb_strlen($full) > 40 ? mb_substr($full, 0, 40) . "…" : $full;
?>
<div class="amenities">
    <?= htmlspecialchars($short ?: '—') ?>
    <?php if (mb_strlen($full) > 40): ?>
        <span class="view-more" data-full="<?= htmlspecialchars($full) ?>">
            View more
        </span>
    <?php endif; ?>
</div>
</td>

<td>$<?= number_format($h['price_per_night']) ?></td>
<td><?= $h['rating'] ?: 'N/A' ?></td>

<td>
<div class="actions">
    <a class="btn edit-btn" href="edit_hotel.php?id=<?= $h['hotel_id'] ?>">✏️ Edit</a>
    <a class="btn delete-btn" href="delete_hotel.php?id=<?= $h['hotel_id'] ?>"
       onclick="return confirmDelete(event, 'delete_hotel.php?id=<?= $h['hotel_id'] ?>')">❌ Delete</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- PAGINATION -->
<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a class="<?= $i==$page?'active':'' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

<!-- ===== MOBILE CARD VIEW ===== -->
<div class="card-list">
<?php foreach ($hotels as $h): ?>
<?php
$fullA = trim($h['amenities'] ?? '');
$shortA = mb_strlen($fullA) > 90 ? mb_substr($fullA, 0, 90) . "…" : $fullA;
?>
<div class="hotel-card">
    <div class="card-top">
        <?php if (!empty($h['image'])): ?>
            <img class="card-img" src="uploads/<?= htmlspecialchars($h['image']) ?>" alt="hotel">
        <?php else: ?>
            <div class="card-img"></div>
        <?php endif; ?>

        <div>
            <p class="card-title"><?= htmlspecialchars($h['name']) ?></p>
            <p class="card-sub"><?= htmlspecialchars($h['destination']) ?> • <?= htmlspecialchars($h['type']) ?></p>
        </div>
    </div>

    <div class="card-meta">
        <div class="meta-item"><b>Price / Night</b>$<?= number_format($h['price_per_night']) ?></div>
        <div class="meta-item"><b>Rating</b><?= $h['rating'] ?: 'N/A' ?></div>
    </div>

    <div class="card-amenities">
        <b>Amenities</b><br>
        <?= htmlspecialchars($shortA ?: '—') ?>
    </div>

    <div class="card-actions">
        <a class="btn edit-btn" href="edit_hotel.php?id=<?= $h['hotel_id'] ?>">✏️ Edit</a>
        <button class="btn delete-btn" type="button"
                onclick="openDeleteModal('delete_hotel.php?id=<?= $h['hotel_id'] ?>')">❌ Delete</button>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>

<!-- ===== DELETE CONFIRM MODAL ===== -->
<div class="modal-bg" id="deleteModal">
    <div class="modal">
        <h3>Delete Hotel?</h3>
        <p>This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn-cancel" type="button" onclick="closeDeleteModal()">Cancel</button>
            <a id="deleteLink" class="btn-confirm" href="#">Yes, Delete</a>
        </div>
    </div>
</div>

<script>
function openDeleteModal(url){
    document.getElementById("deleteLink").href = url;
    document.getElementById("deleteModal").classList.add("show");
}
function closeDeleteModal(){
    document.getElementById("deleteModal").classList.remove("show");
}
function confirmDelete(e, url){
    e.preventDefault();
    openDeleteModal(url);
    return false;
}
</script>

</body>
</html>
