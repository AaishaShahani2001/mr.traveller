<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = $conn->prepare("
    SELECT 
        b.*,
        d.title,
        d.country,
        d.city,
        h.name AS hotel_name,
        h.type AS hotel_type,
        f.transport_type,
        f.provider_name,
        DATEDIFF(b.check_out, b.check_in) AS nights
    FROM bookings b
    JOIN destinations d ON b.dest_id = d.dest_id
    LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
    LEFT JOIN travel_facilities f ON b.facility_id = f.facility_id
    WHERE b.user_id = ?
    ORDER BY b.booking_id DESC
");
$sql->execute([$user_id]);
$bookings = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Bookings | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial, sans-serif; }
body { margin:0; background:#f5f7ff; }

.container {
    max-width:1400px;
    margin:auto;
    padding:40px 20px;
}

.back-btn {
    display:inline-block;
    margin-bottom:20px;
    color:#007bff;
    font-weight:bold;
    text-decoration:none;
}

/* ===== DESKTOP TABLE ===== */
.table-wrap {
    display:block;
}

table {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,0.12);
}

th, td {
    padding:14px;
    border-bottom:1px solid #eee;
    white-space: nowrap;
    text-align:center;
}

th {
    background:#007bff;
    color:white;
}

/* ===== STATUS ===== */
.status {
    padding:6px 16px;
    border-radius:999px;
    font-weight:bold;
    font-size:14px;
}
.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#d4edda; color:#155724; }
.cancelled { background:#f8d7da; color:#721c24; }

/* ===== ACTIONS ===== */
.actions {
    display:flex;
    gap:8px;
    justify-content:center;
    flex-wrap:wrap;
}

.action-btn {
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:bold;
    border:none;
    cursor:pointer;
    text-decoration:none;
}

.edit-btn { background:#e8f0ff; color:#005fcc; }
.cancel-btn { background:#fdecea; color:#c0392b; }
.invoice-btn { background:#eef3ff; color:#2c3e50; }

/* ===== MOBILE CARDS ===== */
.mobile-cards {
    display:none;
    gap:20px;
}

.card {
    background:white;
    padding:18px;
    border-radius:16px;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
}

.card h3 {
    margin:0 0 6px;
}

.card p {
    margin:4px 0;
    font-size:14px;
}

/* ===== MODAL ===== */
.modal-bg {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.55);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}

.modal {
    background:white;
    padding:24px;
    border-radius:16px;
    max-width:420px;
    width:100%;
    text-align:center;
}

.modal-actions {
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:20px;
}

.modal-actions button {
    padding:10px 16px;
    border-radius:10px;
    border:none;
    font-weight:bold;
    cursor:pointer;
}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .table-wrap { display:none; }
    .mobile-cards { display:grid; }
}
</style>
</head>

<body>

<div class="container">

<a href="home.php" class="back-btn">← Back to Home</a>
<h2>My Bookings</h2>

<?php if (!$bookings): ?>
<p>You have no bookings yet.</p>
<?php else: ?>

<!-- ===== DESKTOP TABLE ===== -->
<div class="table-wrap">
<table>
<thead>
<tr>
    <th>Package</th>
    <th>Location</th>
    <th>Hotel</th>
    <th>Transport</th>
    <th>Check-in</th>
    <th>Check-out</th>
    <th>Nights</th>
    <th>People</th>
    <th>Total</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach ($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['title']) ?></td>
<td><?= htmlspecialchars($b['country']) ?> – <?= htmlspecialchars($b['city']) ?></td>
<td><?= htmlspecialchars($b['hotel_name'] ?? 'N/A') ?></td>
<td><?= htmlspecialchars($b['transport_type'] ?? 'N/A') ?></td>
<td><?= $b['check_in'] ?></td>
<td><?= $b['check_out'] ?></td>
<td><?= $b['nights'] ?></td>
<td><?= $b['number_of_people'] ?></td>
<td>$<?= number_format($b['total_amount']) ?></td>
<td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
<td>
<div class="actions">
<a class="action-btn edit-btn" href="user_update_booking.php?id=<?= $b['booking_id'] ?>">✏️ Edit</a>
<?php if ($b['status']==='pending'): ?>
<button class="action-btn cancel-btn" onclick="openModal('user_cancel_booking.php?id=<?= $b['booking_id'] ?>')">❌ Cancel</button>
<?php endif; ?>
<a class="action-btn invoice-btn" href="booking_invoice_print.php?id=<?= $b['booking_id'] ?>" target="_blank">🧾 Invoice</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ===== MOBILE CARDS ===== -->
<div class="mobile-cards">
<?php foreach ($bookings as $b): ?>
<div class="card">
<h3><?= htmlspecialchars($b['title']) ?></h3>
<p><strong>Location:</strong> <?= htmlspecialchars($b['country']) ?> – <?= htmlspecialchars($b['city']) ?></p>
<p><strong>Hotel:</strong> <?= htmlspecialchars($b['hotel_name'] ?? 'N/A') ?></p>
<p><strong>Transport:</strong> <?= htmlspecialchars($b['transport_type'] ?? 'N/A') ?></p>
<p><strong>Dates:</strong> <?= $b['check_in'] ?> → <?= $b['check_out'] ?></p>
<p><strong>People:</strong> <?= $b['number_of_people'] ?></p>
<p><strong>Total:</strong> $<?= number_format($b['total_amount']) ?></p>
<p><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></p>

<div class="actions">
<a class="action-btn edit-btn" href="user_update_booking.php?id=<?= $b['booking_id'] ?>">✏️ Edit</a>
<?php if ($b['status']==='pending'): ?>
<button class="action-btn cancel-btn" onclick="openModal('user_cancel_booking.php?id=<?= $b['booking_id'] ?>')">❌ Cancel</button>
<?php endif; ?>
<a class="action-btn invoice-btn" href="booking_invoice_print.php?id=<?= $b['booking_id'] ?>" target="_blank">🧾 Invoice</a>
</div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
</div>

<!-- ===== MODAL ===== -->
<div class="modal-bg" id="modal">
<div class="modal">
<h3>Cancel Booking</h3>
<p>Are you sure you want to cancel this booking?</p>
<div class="modal-actions">
<button onclick="closeModal()">No</button>
<a id="cancelLink"><button class="cancel-btn">Yes, Cancel</button></a>
</div>
</div>
</div>

<script>
function openModal(url){
    document.getElementById("cancelLink").href = url;
    document.getElementById("modal").style.display = "flex";
}
function closeModal(){
    document.getElementById("modal").style.display = "none";
}
</script>

</body>
</html>
