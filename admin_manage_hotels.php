<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

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
");
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

/* ===== AMENITIES (TRUNCATE + TOOLTIP) ===== */
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
    table {
        font-size:13px;
    }
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

<td>$<?= number_format($h['price_per_night'],2) ?></td>
<td><?= $h['rating'] ?: 'N/A' ?></td>

<td>
<div class="actions">
    <a class="btn edit-btn" href="edit_hotel.php?id=<?= $h['hotel_id'] ?>">✏️ Edit</a>
    <a class="btn delete-btn" href="delete_hotel.php?id=<?= $h['hotel_id'] ?>">❌ Delete</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>
</div>

</body>
</html>
