<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid destination");
}

$dest_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

/* Fetch destination */
$destStmt = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$destStmt->execute([$dest_id]);
$dest = $destStmt->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    die("Destination not found");
}

/* Fetch hotels */
$hotelStmt = $conn->prepare("SELECT * FROM hotels WHERE dest_id = ?");
$hotelStmt->execute([$dest_id]);
$hotels = $hotelStmt->fetchAll(PDO::FETCH_ASSOC);

/* Fetch travel facilities */
$facilityStmt = $conn->prepare("SELECT * FROM travel_facilities WHERE dest_id = ?");
$facilityStmt->execute([$dest_id]);
$facilities = $facilityStmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Booking Submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $check_in    = $_POST['check_in'];
    $check_out   = $_POST['check_out'];
    $people      = (int)$_POST['people'];
    $hotel_id    = (int)$_POST['hotel_id'];
    $facility_id = (int)$_POST['facility_id'];

    // Price comes from JS but stored into total_amount
    $total_amount = (float)$_POST['total_price'];

    $booking_date = date('Y-m-d');

    $insert = $conn->prepare("
        INSERT INTO bookings
        (user_id, dest_id, hotel_id, facility_id,
         booking_date, check_in, check_out,
         number_of_people, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $insert->execute([
        $user_id,
        $dest_id,
        $hotel_id,
        $facility_id,
        $booking_date,
        $check_in,
        $check_out,
        $people,
        $total_amount
    ]);

    header("Location: my_bookings.php?msg=success");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking | <?= htmlspecialchars($dest['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    margin: 0;
    background: linear-gradient(135deg, #eef2ff, #f5f7ff);
}

/* Top bar */
.top-bar {
    max-width: 900px;
    margin: 30px auto 10px;
    display: flex;
    justify-content: space-between;
    padding: 0 10px;
}

.top-bar a {
    text-decoration: none;
    font-weight: 600;
    color: #007bff;
}

/* Container */
.container {
    max-width: 900px;
    margin: 10px auto 40px;
    background: white;
    padding: 32px;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
}

/* Header */
.header {
    text-align: center;
    margin-bottom: 25px;
}

.header h2 {
    margin-bottom: 6px;
    font-size: 28px;
}

.header p {
    color: #555;
    font-weight: 500;
}

/* Form */
label {
    font-weight: 600;
    margin-top: 14px;
    display: block;
}

input, select {
    width: 100%;
    padding: 12px 14px;
    margin-top: 6px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
}

/* Grid */
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Total */
.total-box {
    margin-top: 22px;
    padding: 18px;
    background: #f1f5ff;
    border-radius: 14px;
    text-align: center;
}

.total-box span {
    font-size: 26px;
    font-weight: bold;
    color: #007bff;
}

/* Button */
.btn {
    margin-top: 28px;
    width: 100%;
    padding: 14px;
    font-size: 16px;
    border-radius: 30px;
    border: none;
    font-weight: bold;
    cursor: pointer;
    background: #007bff;
    color: white;
    transition: 0.3s;
}

.btn:hover {
    background: #005fcc;
}

/* Responsive */
@media (max-width: 700px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="top-bar">
    <a href="home.php">← Back to Home</a>
    <a href="view_destination.php?id=<?= $dest_id ?>">← Back to Destination</a>
</div>

<div class="container">

<div class="header">
    <h2>Book Your Trip</h2>
    <p><?= htmlspecialchars($dest['title']) ?> — <?= htmlspecialchars($dest['country']) ?></p>
</div>

<form method="post">

<div class="grid">
    <div>
        <label>Check-in Date</label>
        <input type="date" name="check_in" id="check_in" required>
    </div>
    <div>
        <label>Check-out Date</label>
        <input type="date" name="check_out" id="check_out" required>
    </div>
</div>

<label>Number of People</label>
<input type="number" name="people" id="people" value="1" min="1" required>

<label>Select Accommodation</label>
<select name="hotel_id" id="hotel" required>
    <option value="">Choose Hotel</option>
    <?php foreach ($hotels as $h): ?>
    <option value="<?= $h['hotel_id'] ?>" data-price="<?= $h['price_per_night'] ?>">
        <?= htmlspecialchars($h['name']) ?> — $<?= $h['price_per_night'] ?>/night
    </option>
    <?php endforeach; ?>
</select>

<label>Select Travel Facility</label>
<select name="facility_id" id="facility" required>
    <option value="">Choose Transport</option>
    <?php foreach ($facilities as $f): ?>
    <option value="<?= $f['facility_id'] ?>" data-price="<?= $f['price'] ?>">
        <?= htmlspecialchars($f['transport_type']) ?>
        (<?= htmlspecialchars($f['provider_name']) ?>) — $<?= $f['price'] ?>
    </option>
    <?php endforeach; ?>
</select>

<div class="total-box">
    Total Price: $<span id="total">0.00</span>
</div>

<!-- JS price → PHP → total_amount -->
<input type="hidden" name="total_price" id="total_price">

<button type="submit" class="btn">Confirm Booking</button>

</form>
</div>

<script>
const basePrice = <?= (float)$dest['price'] ?>;

function calculateTotal() {
    const people = Number(document.getElementById("people").value || 1);
    const checkIn = new Date(document.getElementById("check_in").value);
    const checkOut = new Date(document.getElementById("check_out").value);

    let nights = 0;
    if (checkIn && checkOut && checkOut > checkIn) {
        nights = (checkOut - checkIn) / (1000 * 60 * 60 * 24);
    }

    const hotelPrice = Number(
        document.getElementById("hotel").selectedOptions[0]?.dataset.price || 0
    );

    const facilityPrice = Number(
        document.getElementById("facility").selectedOptions[0]?.dataset.price || 0
    );

    const total =
        (basePrice * people) +
        (hotelPrice * nights) +
        facilityPrice;

    document.getElementById("total").textContent = total.toFixed(2);
    document.getElementById("total_price").value = total.toFixed(2);
}

document.querySelectorAll("input, select").forEach(el => {
    el.addEventListener("change", calculateTotal);
});
</script>

</body>
</html>
