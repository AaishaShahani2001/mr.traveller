<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = $conn->prepare("
    SELECT b.*, d.title, d.country, d.city 
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
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }

body { margin:0; background:#f5f7ff; }

.container {
    max-width: 1200px;
    margin: auto;
    padding: 40px 20px;
}

h2 { margin-bottom: 20px; }

/* Back button */
.back-btn {
    display: inline-block;
    margin-bottom: 20px;
    color: #007bff;
    font-weight: bold;
    text-decoration: none;
    transition: transform 0.3s, color 0.3s;
}

.back-btn:hover {
    color: #005fcc;
    transform: translateX(-5px);
}

/* Toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #2ecc71;
    color: white;
    padding: 14px 22px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    z-index: 999;
    opacity: 0;
    transform: translateY(-20px);
    transition: all .5s ease;
}
.toast.show { opacity: 1; transform: translateY(0); }

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    border-radius: 14px;
    overflow: hidden;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #eee;
}

th {
    background: #007bff;
    color: white;
}

.status {
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: bold;
}
.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#d4edda; color:#155724; }
.cancelled { background:#f8d7da; color:#721c24; }

.action-btn {
    margin-right: 10px;
    font-weight: bold;
    cursor: pointer;
    border: none;
    background: none;
}

.cancel-btn { color:#e74c3c; }
.invoice-btn { color:#007bff; }

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
.modal h3 { margin-top: 0; }
.modal button {
    padding: 10px 16px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}
.btn-cancel { background:#e9ecef; }
.btn-confirm { background:#dc3545; color:white; }

/* Responsive */
@media(max-width:768px){
    table, thead, tbody, th, td, tr { display:block; }
    thead { display:none; }
    tr {
        background:white;
        margin-bottom:20px;
        padding:18px;
        border-radius:16px;
        box-shadow:0 10px 30px rgba(0,0,0,0.12);
    }
    td { border:none; padding:6px 0; }
    td::before {
        content: attr(data-label);
        font-weight: bold;
        display:block;
        color:#555;
    }
}
</style>
</head>

<body>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'booked'): ?>
<div class="toast" id="toast">Booking successful 🎉</div>
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
    <th>Date</th>
    <th>People</th>
    <th>Total</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>

<tbody>
<?php foreach ($bookings as $b): ?>
<tr>
<td data-label="Package"><?= htmlspecialchars($b['title']) ?></td>
<td data-label="Location"><?= htmlspecialchars($b['country']) ?> - <?= htmlspecialchars($b['city']) ?></td>
<td data-label="Date"><?= $b['travel_date'] ?></td>
<td data-label="People"><?= $b['number_of_people'] ?></td>
<td data-label="Total">$<?= number_format($b['total_amount'],2) ?></td>
<td data-label="Status">
    <span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
</td>
<td data-label="Actions">
    <a class="action-btn invoice-btn"
       href="booking_invoice_print.php?id=<?= $b['booking_id'] ?>"
       target="_blank">
       Invoice
    </a>

    <?php if ($b['status'] === 'pending'): ?>
        <button class="action-btn cancel-btn"
            onclick="openModal('user_cancel_booking.php?id=<?= $b['booking_id'] ?>')">
            Cancel
        </button>
    <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php endif; ?>
</div>

<!-- Cancel Modal -->
<div class="modal-bg" id="modal">
    <div class="modal">
        <h3>Cancel Booking</h3>
        <p>Are you sure you want to cancel this booking?</p>
        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button class="btn-cancel" onclick="closeModal()">No</button>
            <a id="cancelLink"><button class="btn-confirm">Yes, Cancel</button></a>
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
