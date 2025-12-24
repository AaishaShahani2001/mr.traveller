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

/* ===== TABLE ===== */
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

/* ===== MOBILE ===== */
@media(max-width:1000px){
    table { font-size:13px; }
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

<td>
    <?= htmlspecialchars($b['hotel_name'] ?? 'N/A') ?><br>
    <small>(<?= htmlspecialchars($b['hotel_type'] ?? '-') ?>)</small>
</td>

<td>
    <?= htmlspecialchars($b['transport_type'] ?? 'N/A') ?><br>
    <small><?= htmlspecialchars($b['provider_name'] ?? '-') ?></small>
</td>

<td><?= $b['check_in'] ?></td>
<td><?= $b['check_out'] ?></td>
<td><?= $b['nights'] ?></td>
<td><?= $b['number_of_people'] ?></td>

<td><strong>$<?= number_format($b['total_price'],2) ?></strong></td>

<td>
    <span class="status <?= $b['status'] ?>">
        <?= ucfirst($b['status']) ?>
    </span>
</td>

<td>
<div class="actions">

<a class="action-btn edit-btn"
   href="user_update_booking.php?id=<?= $b['booking_id'] ?>">
   ✏️ Edit
</a>

<?php if ($b['status'] === 'pending'): ?>
<button class="action-btn cancel-btn"
onclick="openModal('user_cancel_booking.php?id=<?= $b['booking_id'] ?>')">
❌ Cancel
</button>
<?php endif; ?>

<a class="action-btn invoice-btn"
   href="booking_invoice_print.php?id=<?= $b['booking_id'] ?>"
   target="_blank">
🧾 Invoice
</a>

</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>
</div>

<!-- ===== CANCEL MODAL ===== -->
<div class="modal-bg" id="modal">
    <div class="modal">
        <h3>Cancel Booking</h3>
        <p>Are you sure you want to cancel this booking?</p>
        <div class="modal-actions">
            <button onclick="closeModal()">No</button>
            <a id="cancelLink">
                <button class="cancel-btn">Yes, Cancel</button>
            </a>
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
