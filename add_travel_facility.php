<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* Fetch destinations */
$stmt = $conn->prepare("SELECT dest_id, title FROM destinations");
$stmt->execute();
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dest_id   = $_POST['dest_id'];
    $type      = $_POST['transport_type'];
    $provider  = $_POST['provider'];
    $price     = $_POST['price'];
    $duration  = $_POST['duration'];

    $insert = $conn->prepare("
        INSERT INTO travel_facilities
        (dest_id, transport_type, provider_name, price, duration)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insert->execute([$dest_id, $type, $provider, $price, $duration]);

    $msg = "Travel facility added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Travel Facility | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box;font-family:"Segoe UI",Arial}
body{margin:0;background:#f5f7ff}

/* ===== TOPBAR (MOBILE) ===== */
.topbar{
    display:none;
    position:fixed;
    top:0;
    left:0;
    right:0;
    height:56px;
    background:#1f2937;
    color:white;
    align-items:center;
    padding:0 16px;
    z-index:1200;
}
.menu-btn{
    font-size:22px;
    cursor:pointer;
}

/* ===== SIDEBAR ===== */
.sidebar{
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    inset:0 auto 0 0;
    transform:translateX(0);
    transition:.3s ease;
    z-index:1100;
}
.sidebar.hide{
    transform:translateX(-100%);
}
.sidebar h2{text-align:center;margin-bottom:30px}
.sidebar a{
    display:block;
    padding:14px 22px;
    color:#e5e7eb;
    text-decoration:none;
    font-weight:500;
}
.sidebar a:hover,.sidebar a.active{
    background:#2563eb;
    color:white;
}

/* ===== OVERLAY ===== */
.overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:1000;
}
.overlay.show{display:block}

/* ===== CONTENT ===== */
.content{
    margin-left:250px;
    padding:40px;
    min-height:100vh;
}

/* ===== CARD ===== */
.card{
    background:white;
    padding:30px;
    border-radius:18px;
    box-shadow:0 20px 50px rgba(0,0,0,.15);
    max-width:900px;
}

/* ===== FORM GRID ===== */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
}
label{font-weight:600;margin-top:12px;display:block}
input,select{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
}

/* ===== BUTTON ===== */
button{
    margin-top:30px;
    width:100%;
    padding:14px;
    border-radius:30px;
    border:none;
    background:#007bff;
    color:white;
    font-weight:bold;
    font-size:16px;
    cursor:pointer;
}

/* ===== TOAST ===== */
.toast{
    position:fixed;
    top:20px;
    right:20px;
    background:#16a34a;
    color:white;
    padding:14px 22px;
    border-radius:12px;
    font-weight:600;
    box-shadow:0 12px 30px rgba(0,0,0,.3);
    opacity:0;
    transform:translateY(-20px);
    transition:.4s ease;
    z-index:9999;
}
.toast.show{opacity:1;transform:translateY(0)}

/* ===== RESPONSIVE ===== */
@media(max-width:900px){
    .topbar{display:flex}
    .sidebar{transform:translateX(-100%)}
    .sidebar.show{transform:translateX(0)}
    .content{
        margin-left:0;
        padding:24px;
        padding-top:80px;
    }
    .grid{grid-template-columns:1fr}
}
</style>
</head>

<body>

<!-- ===== MOBILE TOPBAR ===== -->
<div class="topbar">
    <span class="menu-btn" onclick="toggleMenu()">☰</span>
    <strong>Admin Panel</strong>
</div>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a class="active" href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<!-- ===== CONTENT ===== -->
<div class="content">
<h1>Add Travel Facility</h1>

<div class="card">
<form method="post">

<div class="grid">
<div>
    <label>Destination</label>
    <select name="dest_id" required>
        <option value="">Select Destination</option>
        <?php foreach ($destinations as $d): ?>
        <option value="<?= $d['dest_id'] ?>"><?= htmlspecialchars($d['title']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Transport Type</label>
    <select name="transport_type" required>
        <option>Bus</option><option>Jeep</option>
        <option>Taxi</option><option>Boat</option>
    </select>
</div>

<div>
    <label>Provider Name</label>
    <input type="text" name="provider">
    <label>Price</label>
    <input type="number" name="price">
    <label>Duration</label>
    <input type="text" name="duration">
</div>
</div>

<button>Add Travel Facility</button>
</form>
</div>
</div>

<?php if ($msg): ?>
<div class="toast" id="toast"><?= $msg ?></div>
<script>
const toast=document.getElementById("toast");
setTimeout(()=>toast.classList.add("show"),200);
setTimeout(()=>toast.classList.remove("show"),3200);
</script>
<?php endif; ?>

<script>
function toggleMenu(){
    document.getElementById("sidebar").classList.toggle("show");
    document.getElementById("overlay").classList.toggle("show");
}
</script>

</body>
</html>
