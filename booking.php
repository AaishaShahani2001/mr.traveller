<?php
session_start();
require "config.php";

/* ---------- Login check ---------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------- Validate destination ---------- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid destination!");
}

$dest_id = (int)$_GET['id'];

/* ---------- Fetch destination ---------- */
$sql = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$sql->execute([$dest_id]);
$dest = $sql->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    die("Destination not found!");
}

/* ---------- Handle booking ---------- */
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $travel_date = $_POST['travel_date'];
    $number_of_people = (int)$_POST['number_of_people'];

    if ($number_of_people < 1) {
        $error_msg = "Number of people must be at least 1.";
    } else {
        $user_id = $_SESSION['user_id'];
        $booking_date = date('Y-m-d');
        $total_amount = $dest['price'] * $number_of_people;

        $stmt = $conn->prepare("
            INSERT INTO bookings
            (user_id, dest_id, booking_date, travel_date, number_of_people, total_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->execute([
            $user_id,
            $dest_id,
            $booking_date,
            $travel_date,
            $number_of_people,
            $total_amount
        ]);

        header("Location: my_bookings.php?msg=booked");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Book: <?= htmlspecialchars($dest['title']) ?> | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    margin: 0;
    background: #f5f7ff;
}

/* Container */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 40px 20px;
}

/* Back link */
.back-link {
    display: inline-block;
    margin-bottom: 18px;
    font-size: 15px;
    font-weight: 600;
    color: #007bff;
    text-decoration: none;
    transition: transform 0.3s, color 0.3s;
}
.back-link:hover {
    color: #005fcc;
    transform: translateX(-4px);
}

/* Card */
.booking-box {
    background: white;
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    display: flex;
    gap: 30px;
    align-items: center;
}

/* Image */
.image-box {
    flex: 1;
}

.image-box img {
    width: 100%;
    height: 380px;
    object-fit: contain;        /* ✅ no crop */
    background: #f1f3ff;
    border-radius: 16px;
}

/* Form */
.form-box {
    flex: 1.2;
}

.form-box h2 {
    font-size: 32px;
    margin-bottom: 6px;
}

.location {
    font-weight: 600;
    color: #555;
    margin-bottom: 10px;
}

.price {
    font-size: 22px;
    color: #007bff;
    font-weight: bold;
    margin-bottom: 8px;
}

.duration {
    margin-bottom: 18px;
    color: #444;
}

/* Form fields */
label {
    display: block;
    margin-top: 14px;
    font-weight: 600;
}

input {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
}

button {
    width: 100%;
    margin-top: 20px;
    padding: 14px;
    border-radius: 30px;
    border: none;
    background: #007bff;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}

button:hover {
    background: #005fcc;
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

/* Error */
.error {
    margin-top: 15px;
    color: #c0392b;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 900px) {
    .booking-box {
        flex-direction: column;
        text-align: center;
    }

    .image-box img {
        height: 300px;
    }
}
</style>
</head>

<body>

<div class="container">

    <a href="view_destination.php?id=<?= $dest['dest_id'] ?>" class="back-link">
        ← Back to Destination
    </a>

    <div class="booking-box">

        <!-- IMAGE -->
        <div class="image-box">
            <img src="uploads/<?= htmlspecialchars($dest['image']) ?>" alt="Destination">
        </div>

        <!-- FORM -->
        <div class="form-box">
            <h2><?= htmlspecialchars($dest['title']) ?></h2>

            <p class="location">
                <?= htmlspecialchars($dest['country']) ?> — <?= htmlspecialchars($dest['city']) ?>
            </p>

            <p class="price">$<?= number_format($dest['price'],2) ?> per person</p>
            <p class="duration">Duration: <?= htmlspecialchars($dest['duration']) ?></p>

            <form method="POST">
                <label>Travel Date</label>
                <input type="date" name="travel_date" required>

                <label>Number of People</label>
                <input type="number" name="number_of_people" value="1" min="1" required>

                <button type="submit">Confirm Booking</button>
            </form>

            <?php if (!empty($error_msg)): ?>
                <p class="error"><?= htmlspecialchars($error_msg) ?></p>
            <?php endif; ?>
        </div>

    </div>

</div>

</body>
</html>
