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
        DATEDIFF(b.check_out, b.check_in) AS nights
    FROM bookings b
    JOIN destinations d ON b.dest_id = d.dest_id
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
    max-width:1200px;
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

/* ===== TOAST ===== */
.toast {
    position:fixed;
    top:20px;
    right:20px;
    background:#2ecc71;
    color:white;
    padding:14px 22px;
    border-radius:10px;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    opacity:0;
    transform:translateY(-20px);
    transition:.5s;
    z-index:9999;
}
.toast.show { opacity:1; transform:translateY(0); }

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
    white-space: nowrap; /* 🔑 keep in one line */
}

th {
    background:#007bff;
    color:white;
}

/* Fixed column widths */
td:nth-child(3),
td:nth-child(4) {
    width:120px;
    text-align:center;
}

/* ===== STATUS ===== */
.status {
    padding:6px 16px;
    border-radius:999px;
    font-weight:bold;
    font-size:14px;
    display:inline-block;
}
.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#d4edda; color:#155724; }
.cancelled { background:#f8d7da; color:#721c24; }

/* ===== ACTIONS ===== */
.actions {
    display:flex;
    gap:8px;
    align-items:center;
}

.action-btn {
    padding:8px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:bold;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:none;
    cursor:pointer;
}

/* Buttons */
.edit-btn {
    background:#e8f0ff;
    color:#005fcc;
}
.edit-btn:hover { background:#d6e4ff; }

.cancel-btn {
    background:#fdecea;
    color:#c0392b;
}
.cancel-btn:hover { background:#fadbd8; }

.invoice-btn {
    background:#eef3ff;
    color:#2c3e50;
}
.invoice-btn:hover { background:#dfe7ff; }

/* ===== MODAL ===== */
.modal-bg {
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.55);
    display:none;
    justify-content:center;
    align-items:center;
}
.modal-bg.show { display:flex; }

.modal {
    background:white;
    padding:22px;
    border-radius:16px;
    max-width:420px;
    width:100%;
}
.modal-actions {
    display:flex;
    justify-content:flex-end;
    gap:10px;
}
.modal button {
    padding:10px 16px;
    border-radius:10px;
    border:none;
    font-weight:bold;
    cursor:pointer;
}
.btn-close { background:#e9ecef; }
.btn-confirm { background:#dc3545; color:white; }

/* ===== MOBILE ===== */
@media(max-width:900px){
    table { font-size:14px; }
}
</style>
</head>

<body>

<!-- ===== SUCCESS TOAST ===== -->
<?php if (isset($_GET['msg'])): ?>
<div class="toast" id="toast">
    <?= $_GET['msg']==='updated' ? 'Booking updated successfully ✅' : 'Booking successful 🎉' ?>
</div>
<script>
const toast = document.getElementById("toast");
setTimeout(()=>toast.classList.add("show"),200);
setTimeout(()=>toast.classList.remove("show"),3000);
</script>
<?php endif; ?>

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
<td><?= $b['check_in'] ?></td>
<td><?= $b['check_out'] ?></td>
<td><?= $b['nights'] ?></td>
<td><?= $b['number_of_people'] ?></td>
<td>$<?= number_format($b['total_amount'],2) ?></td>
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
            <button class="btn-close" onclick="closeModal()">No</button>
            <a id="cancelLink">
                <button class="btn-confirm">Yes, Cancel</button>
            </a>
        </div>
    </div>
</div>

<script>
function openModal(url){
    document.getElementById("cancelLink").href = url;
    document.getElementById("modal").classList.add("show");
}
function closeModal(){
    document.getElementById("modal").classList.remove("show");
}
</script>

</body>
</html>
