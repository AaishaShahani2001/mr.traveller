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

    $check_in  = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $number_of_people = (int)($_POST['number_of_people'] ?? 0);

    $today = date('Y-m-d');

    if ($number_of_people < 1) {
        $error_msg = "Number of people must be at least 1.";
    }
    elseif (!$check_in || !$check_out) {
        $error_msg = "Please select check-in and check-out dates.";
    }
    elseif ($check_in < $today) {
        $error_msg = "Check-in date cannot be in the past.";
    }
    elseif ($check_out <= $check_in) {
        $error_msg = "Check-out date must be after check-in date.";
    }
    else {

        /* ---------- Overlap check ---------- */
        $overlapStmt = $conn->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE dest_id = ?
              AND status IN ('pending','confirmed')
              AND check_in < ?
              AND check_out > ?
        ");

        $overlapStmt->execute([
            $dest_id,
            $check_out,
            $check_in
        ]);

        $overlaps = $overlapStmt->fetchColumn();

        if ($overlaps > 0) {
            $error_msg = "❌ This destination is already booked for selected dates.";
        } else {

            $user_id = $_SESSION['user_id'];
            $booking_date = date('Y-m-d');

            /* ---------- Calculate nights ---------- */
            $start = new DateTime($check_in);
            $end   = new DateTime($check_out);
            $nights = $start->diff($end)->days;

            if ($nights < 1) {
                $error_msg = "Invalid booking duration.";
            } else {

                /* ---------- Total amount ---------- */
                $total_amount = $dest['price'] * $number_of_people * $nights;

                $stmt = $conn->prepare("
                    INSERT INTO bookings
                    (user_id, dest_id, booking_date, check_in, check_out, number_of_people, total_amount, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                $stmt->execute([
                    $user_id,
                    $dest_id,
                    $booking_date,
                    $check_in,
                    $check_out,
                    $number_of_people,
                    $total_amount
                ]);

                header("Location: my_bookings.php?msg=booked");
                exit;
            }
        }
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
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }
body { margin: 0; background: #f5f7ff; }

.container { max-width: 1200px; margin: auto; padding: 40px 20px; }

.back-link {
    display: inline-block;
    margin-bottom: 18px;
    font-size: 15px;
    font-weight: 600;
    color: #007bff;
    text-decoration: none;
}

.booking-box {
    background: white;
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    display: flex;
    gap: 30px;
    align-items: center;
}

.image-box { flex: 1; }

.image-box img {
    width: 100%;
    height: 380px;
    object-fit: contain;
    background: #f1f3ff;
    border-radius: 16px;
}

.form-box { flex: 1.2; }

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
}

button {
    width: 100%;
    margin-top: 22px;
    padding: 14px;
    border-radius: 30px;
    border: none;
    background: #007bff;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.error {
    margin-top: 15px;
    color: #c0392b;
    font-weight: 600;
}

@media (max-width: 900px) {
    .booking-box {
        flex-direction: column;
        text-align: center;
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

<div class="image-box">
    <img src="uploads/<?= htmlspecialchars($dest['image']) ?>">
</div>

<div class="form-box">
    <h2><?= htmlspecialchars($dest['title']) ?></h2>
    <p><?= htmlspecialchars($dest['country']) ?> — <?= htmlspecialchars($dest['city']) ?></p>
    <p><strong>$<?= number_format($dest['price'],2) ?></strong> per person / per night</p>

    <form method="POST">

        <label>Check-in Date</label>
        <input type="date" name="check_in" min="<?= date('Y-m-d') ?>" required>

        <label>Check-out Date</label>
        <input type="date" name="check_out" min="<?= date('Y-m-d') ?>" required>

        <label>Number of People</label>
        <input type="number" name="number_of_people" value="1" min="1" required>

        <button type="submit">Confirm Booking</button>
    </form>

    <?php if ($error_msg): ?>
        <p class="error"><?= htmlspecialchars($error_msg) ?></p>
    <?php endif; ?>

</div>
</div>
</div>

</body>
</html>
