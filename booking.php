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

/* ---------- Helpers ---------- */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/* ---------- Booking Submit ---------- */
$errors = [];
$toast = ""; // toast text
$toastType = "error"; // error | success

// keep sticky values (does not change UI)
$old = [
    'check_in' => '',
    'check_out' => '',
    'people' => '1',
    'hotel_id' => '',
    'facility_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $check_in    = trim($_POST['check_in'] ?? '');
    $check_out   = trim($_POST['check_out'] ?? '');
    $people      = (int)($_POST['people'] ?? 0);
    $hotel_id    = (int)($_POST['hotel_id'] ?? 0);
    $facility_id = (int)($_POST['facility_id'] ?? 0);

    // sticky
    $old['check_in'] = $check_in;
    $old['check_out'] = $check_out;
    $old['people'] = (string)max(1, $people);
    $old['hotel_id'] = (string)$hotel_id;
    $old['facility_id'] = (string)$facility_id;

    /* ---- Validate Dates ---- */
    if ($check_in === '' || !isValidDate($check_in)) {
        $errors[] = "Please select a valid Check-in date.";
    }
    if ($check_out === '' || !isValidDate($check_out)) {
        $errors[] = "Please select a valid Check-out date.";
    }

    $nights = 0;
    if (!$errors) {
        $in = new DateTime($check_in);
        $out = new DateTime($check_out);

        if ($out <= $in) {
            $errors[] = "Check-out date must be after Check-in date.";
        } else {
            $nights = (int)$out->diff($in)->days; // positive days
            if ($nights <= 0) {
                $errors[] = "Number of nights must be at least 1.";
            }
        }
    }

    /* ---- Validate People ---- */
    if ($people < 1 || $people > 50) {
        $errors[] = "Number of people must be between 1 and 50.";
    }

    /* ---- Validate Hotel ---- */
    if ($hotel_id <= 0) {
        $errors[] = "Please select an accommodation.";
    } else {
        $checkHotel = $conn->prepare("SELECT price_per_night FROM hotels WHERE hotel_id = ? AND dest_id = ?");
        $checkHotel->execute([$hotel_id, $dest_id]);
        $hotelRow = $checkHotel->fetch(PDO::FETCH_ASSOC);
        if (!$hotelRow) {
            $errors[] = "Invalid accommodation selection.";
        }
    }

    /* ---- Validate Facility ---- */
    if ($facility_id <= 0) {
        $errors[] = "Please select a travel facility.";
    } else {
        $checkFac = $conn->prepare("SELECT price FROM travel_facilities WHERE facility_id = ? AND dest_id = ?");
        $checkFac->execute([$facility_id, $dest_id]);
        $facRow = $checkFac->fetch(PDO::FETCH_ASSOC);
        if (!$facRow) {
            $errors[] = "Invalid travel facility selection.";
        }
    }

    /* ---- Secure Total Calculation (do NOT trust JS total_price) ---- */
    $basePrice = (float)$dest['price'];
    $hotelPrice = isset($hotelRow) ? (float)$hotelRow['price_per_night'] : 0.0;
    $facilityPrice = isset($facRow) ? (float)$facRow['price'] : 0.0;

    $total_amount = ($basePrice * $people) + ($hotelPrice * $nights) + $facilityPrice;

    if ($total_amount <= 0) {
        $errors[] = "Total price is invalid. Please re-check your booking details.";
    }

    if (!$errors) {
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
    } else {
        // show toast with errors
        $toast = implode("<br>", array_map('htmlspecialchars', $errors));
        $toastType = "error";
    }
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

/* Toast (NEW - does not change UI layout) */
.toast{
    position:fixed;
    top:18px;
    right:18px;
    max-width:340px;
    background:#dc2626;
    color:#fff;
    padding:14px 16px;
    border-radius:14px;
    box-shadow:0 18px 55px rgba(0,0,0,.25);
    font-weight:700;
    line-height:1.35;
    opacity:0;
    transform:translateY(-10px);
    transition:.35s ease;
    z-index:9999;
}
.toast.show{opacity:1;transform:translateY(0)}
.toast.success{background:#16a34a}
.toast .small{font-weight:600;opacity:.95;font-size:13px;margin-top:6px}

/* Responsive */
@media (max-width: 700px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<?php if (!empty($toast)): ?>
<div class="toast <?= $toastType === 'success' ? 'success' : '' ?>" id="toast">
    <?= $toast ?>
</div>
<script>
  setTimeout(()=>document.getElementById("toast").classList.add("show"), 100);
  setTimeout(()=>document.getElementById("toast").classList.remove("show"), 4200);
</script>
<?php endif; ?>

<div class="top-bar">
    <a href="home.php">← Back to Home</a>
    <a href="view_destination.php?id=<?= $dest_id ?>">← Back to Destination</a>
</div>

<div class="container">

<div class="header">
    <h2>Book Your Trip</h2>
    <p><?= htmlspecialchars($dest['title']) ?> — <?= htmlspecialchars($dest['country']) ?></p>
</div>

<form method="post" id="bookingForm">

<div class="grid">
    <div>
        <label>Check-in Date</label>
        <input type="date" name="check_in" id="check_in" required value="<?= htmlspecialchars($old['check_in']) ?>">
    </div>
    <div>
        <label>Check-out Date</label>
        <input type="date" name="check_out" id="check_out" required value="<?= htmlspecialchars($old['check_out']) ?>">
    </div>
</div>

<label>Number of People</label>
<input type="number" name="people" id="people" value="<?= htmlspecialchars($old['people']) ?>" min="1" required>

<label>Select Accommodation</label>
<select name="hotel_id" id="hotel" required>
    <option value="">Choose Hotel</option>
    <?php foreach ($hotels as $h): ?>
    <option value="<?= $h['hotel_id'] ?>"
            data-price="<?= $h['price_per_night'] ?>"
            <?= ((string)$h['hotel_id'] === (string)$old['hotel_id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($h['name']) ?> — $<?= $h['price_per_night'] ?>/night
    </option>
    <?php endforeach; ?>
</select>

<label>Select Travel Facility</label>
<select name="facility_id" id="facility" required>
    <option value="">Choose Transport</option>
    <?php foreach ($facilities as $f): ?>
    <option value="<?= $f['facility_id'] ?>"
            data-price="<?= $f['price'] ?>"
            <?= ((string)$f['facility_id'] === (string)$old['facility_id']) ? 'selected' : '' ?>>
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

function showToast(message, type = "error"){
  let toast = document.getElementById("clientToast");
  if(!toast){
    toast = document.createElement("div");
    toast.id = "clientToast";
    toast.className = "toast";
    document.body.appendChild(toast);
  }
  toast.className = "toast" + (type === "success" ? " success" : "");
  toast.innerHTML = message;
  toast.classList.add("show");
  clearTimeout(window.__toastTimer1);
  clearTimeout(window.__toastTimer2);
  window.__toastTimer1 = setTimeout(()=>toast.classList.remove("show"), 4200);
}

function calculateTotal() {
    const people = Number(document.getElementById("people").value || 1);
    const checkInVal = document.getElementById("check_in").value;
    const checkOutVal = document.getElementById("check_out").value;

    const checkIn = checkInVal ? new Date(checkInVal) : null;
    const checkOut = checkOutVal ? new Date(checkOutVal) : null;

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
    el.addEventListener("input", calculateTotal);
});

// initial calc for sticky values
calculateTotal();

/* ----- Client-side validation on submit ----- */
document.getElementById("bookingForm").addEventListener("submit", function(e){
    const errs = [];

    const checkIn = document.getElementById("check_in").value;
    const checkOut = document.getElementById("check_out").value;
    const people = Number(document.getElementById("people").value || 0);
    const hotel = document.getElementById("hotel").value;
    const facility = document.getElementById("facility").value;

    if(!checkIn) errs.push("Please select a Check-in date.");
    if(!checkOut) errs.push("Please select a Check-out date.");

    if(checkIn && checkOut){
        const inD = new Date(checkIn);
        const outD = new Date(checkOut);
        if(!(outD > inD)) errs.push("Check-out date must be after Check-in date.");
    }

    if(!Number.isFinite(people) || people < 1) errs.push("Number of people must be at least 1.");
    if(!hotel) errs.push("Please select an accommodation.");
    if(!facility) errs.push("Please select a travel facility.");

    // prevent submit if errors
    if(errs.length){
        e.preventDefault();
        showToast(errs.map(x => "• " + x).join("<br>"), "error");
        return;
    }

    // ensure total is computed
    calculateTotal();
});

/* =====  PAST DATE DISABLE LOGIC ===== */
const today = new Date().toISOString().split("T")[0];

const checkIn = document.getElementById("check_in");
const checkOut = document.getElementById("check_out");

checkIn.min = today;
checkOut.min = today;

checkIn.addEventListener("change", () => {
    if (checkIn.value) {
        checkOut.min = checkIn.value;
    } else {
        checkOut.min = today;
    }
});
</script>

</body>
</html>
