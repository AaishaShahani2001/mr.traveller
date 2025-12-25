<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* Fetch travel facilities with destination */
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
    ORDER BY f.facility_id DESC
");
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Optional toast message */
$msg = $_GET['msg'] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Travel Facilities | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial; }
body { margin:0; background:#f5f7ff; }

/* ===== SIDEBAR ===== */
.sidebar{
    width:250px; background:#1f2937; color:#fff;
    padding-top:30px; position:fixed; inset:0 auto 0 0;
}
.sidebar h2{ text-align:center; margin-bottom:30px; }
.sidebar a{
    display:block; padding:14px 22px; color:#e5e7eb;
    text-decoration:none; font-weight:500;
}
.sidebar a:hover, .sidebar a.active{ background:#2563eb; color:#fff; }

/* ===== CONTENT ===== */
.content{
    margin-left:250px;
    padding:40px;
    min-height:100vh;
}
h1{ margin:0 0 18px; }

/* ===== TOAST ===== */
.toast{
    display:none;
    background:#d4edda;
    color:#155724;
    padding:12px 16px;
    border-radius:12px;
    margin-bottom:18px;
    font-weight:600;
    max-width:900px;
}
.toast.show{ display:block; }

/* ===== TABLE ===== */
table{
    width:100%;
    background:#fff;
    border-collapse:collapse;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
}
th, td{
    padding:14px;
    border-bottom:1px solid #eee;
    text-align:center;
    font-size:14px;
    white-space:nowrap;
}
th{ background:#2563eb; color:#fff; }

/* ===== ACTION BUTTONS ===== */
.actions{ display:flex; gap:8px; justify-content:center; }
.btn{
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:bold;
    text-decoration:none;
    border:none;
    cursor:pointer;
}
.edit-btn{ background:#e8f0ff; color:#005fcc; }
.edit-btn:hover{ background:#d6e4ff; }
.delete-btn{ background:#fdecea; color:#c0392b; }
.delete-btn:hover{ background:#fadbd8; }

/* ===== EMPTY ===== */
.empty{
    background:#fff;
    padding:40px;
    border-radius:16px;
    text-align:center;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
    max-width:900px;
}

/* ===== MODAL ===== */
.modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.6);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}
.modal-overlay.show{ display:flex; }

.modal-box{
    background:#fff;
    padding:24px;
    border-radius:16px;
    width:100%;
    max-width:420px;
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.25);
}
.modal-actions{
    margin-top:18px;
    display:flex;
    justify-content:center;
    gap:12px;
}
.modal-actions .m-btn{
    padding:10px 18px;
    border-radius:999px;
    border:none;
    font-weight:bold;
    cursor:pointer;
}
.m-cancel{ background:#e5e7eb; color:#111; }
.m-delete{ background:#dc2626; color:#fff; text-decoration:none; display:inline-flex; align-items:center; }

/* ===== RESPONSIVE ===== */
@media(max-width:1000px){
    .sidebar{ position:relative; width:100%; }
    .content{ margin-left:0; padding:24px; }
    th, td{ white-space:normal; }
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
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a class="active" href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- ===== CONTENT ===== -->
<div class="content">
    <h1>Manage Travel Facilities</h1>

    <div class="toast <?= $msg ? 'show' : '' ?>">
        <?php
        if ($msg === "updated") echo "Travel facility updated successfully ✅";
        else if ($msg === "deleted") echo "Travel facility deleted successfully 🗑️";
        else echo "";
        ?>
    </div>

    <?php if (!$facilities): ?>
        <div class="empty">
            <h3>No travel facilities added yet</h3>
            <p>Add travel facilities and manage them here.</p>
        </div>
    <?php else: ?>

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
        <?php foreach ($facilities as $f): ?>
        <tr>
            <td><?= htmlspecialchars($f['destination']) ?></td>
            <td><?= htmlspecialchars($f['transport_type']) ?></td>
            <td><?= htmlspecialchars($f['provider_name'] ?: '—') ?></td>
            <td><?= htmlspecialchars($f['duration'] ?: '—') ?></td>
            <td>$<?= number_format((float)$f['price'], 2) ?></td>
            <td>
                <div class="actions">
                    <a class="btn edit-btn" href="edit_travel_facility.php?id=<?= $f['facility_id'] ?>">✏️ Edit</a>
                    <button class="btn delete-btn" onclick="openDeleteModal(<?= $f['facility_id'] ?>)">❌ Delete</button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
</div>

<!-- ===== DELETE MODAL ===== -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <h3>Delete Travel Facility</h3>
        <p>Are you sure you want to delete this travel facility?<br>This action cannot be undone.</p>

        <div class="modal-actions">
            <button class="m-btn m-cancel" onclick="closeDeleteModal()">Cancel</button>
            <a id="confirmDeleteBtn" class="m-btn m-delete">Yes, Delete</a>
        </div>
    </div>
</div>

<script>
function openDeleteModal(id){
    document.getElementById("confirmDeleteBtn").href = "delete_travel_facility.php?id=" + id;
    document.getElementById("deleteModal").classList.add("show");
}
function closeDeleteModal(){
    document.getElementById("deleteModal").classList.remove("show");
}
</script>

</body>
</html>
