<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== Pagination ===== */
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

/* Count */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$countStmt->execute([$user_id]);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/* Fetch */
$sql = $conn->prepare("
    SELECT 
        b.*,
        d.title,
        d.country,
        d.city,
        h.name AS hotel_name,
        h.type AS hotel_type,
        f.transport_type,
        f.provider_name
    FROM bookings b
    JOIN destinations d ON b.dest_id = d.dest_id
    LEFT JOIN hotels h ON b.hotel_id = h.hotel_id
    LEFT JOIN travel_facilities f ON b.facility_id = f.facility_id
    WHERE b.user_id = ?
    ORDER BY b.booking_id DESC
    LIMIT $perPage OFFSET $offset
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
*{box-sizing:border-box;font-family:"Segoe UI",Arial}
body{margin:0;background:#f5f7ff}

.container{max-width:1400px;margin:auto;padding:40px 20px}
.back-btn{color:#007bff;font-weight:bold;text-decoration:none}

/* ===== DESKTOP TABLE (UNCHANGED) ===== */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 15px 40px rgba(0,0,0,.12);
}
th,td{
    padding:14px;
    border-bottom:1px solid #eee;
    text-align:center;
}
th{background:#007bff;color:white}

/* ===== STATUS ===== */
.status{
    padding:6px 16px;
    border-radius:999px;
    font-weight:bold;
}
.pending{background:#fff3cd;color:#856404}
.confirmed{background:#d4edda;color:#155724}
.cancelled{background:#f8d7da;color:#721c24}

/* ===== ACTIONS ===== */
.actions{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
.btn{
    padding:8px 14px;
    border-radius:999px;
    border:none;
    font-weight:bold;
    cursor:pointer;
}
.view-btn{background:#eef3ff;color:#1e40af}
.edit-btn{background:#e8f0ff;color:#005fcc}
.cancel-btn{background:#fdecea;color:#c0392b}
.invoice-btn{background:#eef3ff;color:#2c3e50}

/* ===== PAGINATION ===== */
.pagination{
    display:flex;
    justify-content:center;
    gap:8px;
    margin-top:24px;
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
    background:#007bff;
    color:white;
}

/* ===== MOBILE CARDS ===== */
.mobile-cards{display:none}
.booking-card{
    background:white;
    border-radius:18px;
    padding:18px;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
}
.card-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.card-header h3{
    margin:0;
    font-size:18px;
}
.card-location{
    margin:6px 0 12px;
    color:#555;
    font-size:14px;
}
.card-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    font-size:14px;
}
.card-item{
    background:#f1f5ff;
    padding:10px;
    border-radius:12px;
}
.card-item b{
    display:block;
    font-size:12px;
    color:#555;
    margin-bottom:4px;
}
.card-actions{
    margin-top:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.card-actions .btn{flex:1}

/* ===== VIEW MODAL ===== */
.modal-bg{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:none;
    justify-content:center;
    align-items:center;
    z-index:9999;
}
.modal{
    background:white;
    padding:24px;
    border-radius:16px;
    max-width:420px;
    width:100%;
}
.modal h3{margin-top:0}
.modal p{margin:6px 0}
.modal button{
    margin-top:14px;
    width:100%;
    padding:10px;
    border:none;
    border-radius:999px;
    background:#007bff;
    color:white;
    font-weight:bold;
}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    table{display:none}
    .mobile-cards{
        display:grid;
        gap:18px;
    }
}
</style>
</head>

<body>

<div class="container">

<a href="home.php" class="back-btn">← Back to Home</a>
<h2>My Bookings</h2>

<?php if(!$bookings): ?>
<p>You have no bookings yet.</p>
<?php else: ?>

<!-- ===== DESKTOP TABLE (UNCHANGED) ===== -->
<table>
<thead>
<tr>
    <th>Package</th>
    <th>Location</th>
    <th>Check-in</th>
    <th>Check-out</th>
    <th>People</th>
    <th>Total</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach($bookings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['title']) ?></td>
<td><?= htmlspecialchars($b['country']) ?> – <?= htmlspecialchars($b['city']) ?></td>
<td><?= $b['check_in'] ?></td>
<td><?= $b['check_out'] ?></td>
<td><?= $b['number_of_people'] ?></td>
<td>$<?= number_format($b['total_amount']) ?></td>
<td><span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
<td>
<div class="actions">
<button class="btn view-btn" onclick='openView(<?= json_encode($b) ?>)'>View</button>
<a class="btn edit-btn" href="user_update_booking.php?id=<?= $b['booking_id'] ?>">Edit</a>
<?php if($b['status']==='pending'): ?>
<button class="btn cancel-btn" onclick="openCancel('user_cancel_booking.php?id=<?= $b['booking_id'] ?>')">Cancel</button>
<?php endif; ?>
<a class="btn invoice-btn" href="booking_invoice_print.php?id=<?= $b['booking_id'] ?>" target="_blank">Invoice</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- ===== MOBILE CARDS ===== -->
<div class="mobile-cards">
<?php foreach($bookings as $b): ?>
<div class="booking-card">
    <div class="card-header">
        <h3><?= htmlspecialchars($b['title']) ?></h3>
        <span class="status <?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
    </div>

    <div class="card-location">
        <?= htmlspecialchars($b['country']) ?> – <?= htmlspecialchars($b['city']) ?>
    </div>

    <div class="card-grid">
        <div class="card-item"><b>Check-in</b><?= $b['check_in'] ?></div>
        <div class="card-item"><b>Check-out</b><?= $b['check_out'] ?></div>
        <div class="card-item"><b>People</b><?= $b['number_of_people'] ?></div>
        <div class="card-item"><b>Total</b>$<?= number_format($b['total_amount']) ?></div>
    </div>

    <div class="card-actions">
        <button class="btn view-btn" onclick='openView(<?= json_encode($b) ?>)'>View</button>
        <a class="btn edit-btn" href="user_update_booking.php?id=<?= $b['booking_id'] ?>">Edit</a>
        <?php if($b['status']==='pending'): ?>
            <button class="btn cancel-btn" onclick="openCancel('user_cancel_booking.php?id=<?= $b['booking_id'] ?>')">Cancel</button>
        <?php endif; ?>
        <a class="btn invoice-btn" href="booking_invoice_print.php?id=<?= $b['booking_id'] ?>" target="_blank">Invoice</a>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a class="<?= $i==$page?'active':'' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
<?php endfor; ?>
</div>

<?php endif; ?>
</div>

<!-- VIEW MODAL -->
<div class="modal-bg" id="viewModal">
<div class="modal" id="viewContent"></div>
</div>

<script>
function openView(b){
    document.getElementById("viewContent").innerHTML = `
        <h3>Booking Details</h3>
        <p><b>Package:</b> ${b.title}</p>
        <p><b>Location:</b> ${b.country} – ${b.city}</p>
        <p><b>Hotel:</b> ${b.hotel_name ?? 'N/A'} (${b.hotel_type ?? ''})</p>
        <p><b>Transport:</b> ${b.transport_type ?? 'N/A'} (${b.provider_name ?? ''})</p>
        <p><b>Dates:</b> ${b.check_in} → ${b.check_out}</p>
        <p><b>People:</b> ${b.number_of_people}</p>
        <p><b>Total:</b> $${b.total_amount}</p>
        <button onclick="closeView()">Close</button>
    `;
    document.getElementById("viewModal").style.display="flex";
}
function closeView(){
    document.getElementById("viewModal").style.display="none";
}
function openCancel(url){
    if(confirm("Are you sure you want to cancel this booking?")){
        location.href = url;
    }
}
</script>

</body>
</html>
