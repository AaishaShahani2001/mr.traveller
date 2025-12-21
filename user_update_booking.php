<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid booking");
}

$user_id    = $_SESSION['user_id'];
$booking_id = (int)$_GET['id'];

/* ---------- Fetch booking (ANY status) ---------- */
$stmt = $conn->prepare("
    SELECT b.*, d.price, d.title
    FROM bookings b
    JOIN destinations d ON b.dest_id = d.dest_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user_id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    die("Booking not found");
}

/* ---------- Only pending can be edited ---------- */
$isEditable = ($b['status'] === 'pending');
$error = "";

/* ---------- Handle Update ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isEditable) {
        $error = "❌ This booking can no longer be edited.";
    } else {

        $check_in  = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $people    = (int)$_POST['number_of_people'];

        if ($check_in >= $check_out) {
            $error = "Check-out must be after check-in";
        } elseif ($check_in < date('Y-m-d')) {
            $error = "Check-in cannot be in the past";
        } elseif ($people < 1) {
            $error = "Invalid number of people";
        } else {

            $nights = (new DateTime($check_in))->diff(new DateTime($check_out))->days;
            $total  = $b['price'] * $people * $nights;

            $upd = $conn->prepare("
                UPDATE bookings
                SET check_in = ?, check_out = ?, number_of_people = ?, total_amount = ?
                WHERE booking_id = ? AND user_id = ? AND status = 'pending'
            ");
            $upd->execute([
                $check_in,
                $check_out,
                $people,
                $total,
                $booking_id,
                $user_id
            ]);

            header("Location: my_bookings.php?msg=updated");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Booking | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial, sans-serif; }
body { margin:0; background:#f5f7ff; }

.container {
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.card {
    background:white;
    width:100%;
    max-width:460px;
    padding:28px;
    border-radius:18px;
    box-shadow:0 18px 45px rgba(0,0,0,.15);
}

.card h2 {
    margin:0 0 6px;
    font-size:28px;
    color:#007bff;
}

.card p {
    margin:0 0 14px;
    color:#555;
    font-weight:600;
}

.badge {
    display:inline-block;
    padding:6px 14px;
    border-radius:999px;
    font-weight:bold;
    margin-bottom:18px;
}

.pending { background:#fff3cd; color:#856404; }
.confirmed { background:#d4edda; color:#155724; }
.cancelled { background:#f8d7da; color:#721c24; }

label {
    display:block;
    margin-top:14px;
    font-weight:600;
}

input {
    width:100%;
    padding:12px 14px;
    margin-top:6px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:15px;
}

input:disabled {
    background:#f1f3f5;
    cursor:not-allowed;
}

.actions {
    display:flex;
    gap:12px;
    margin-top:24px;
}

button, .cancel-btn {
    flex:1;
    padding:14px;
    border-radius:30px;
    border:none;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
}

.update-btn {
    background:#007bff;
    color:white;
}
.update-btn:disabled {
    background:#9bbcf5;
    cursor:not-allowed;
}

.cancel-btn {
    background:#e9ecef;
    color:#333;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
}

.error {
    margin-top:14px;
    color:#c0392b;
    background:#fdecea;
    padding:12px 14px;
    border-radius:10px;
    font-weight:600;
}
</style>
</head>

<body>

<div class="container">

<div class="card">

    <h2>Edit Booking</h2>
    <p><?= htmlspecialchars($b['title']) ?></p>

    <span class="badge <?= $b['status'] ?>">
        <?= ucfirst($b['status']) ?>
    </span>

    <form method="POST">

        <label>Check-in Date</label>
        <input type="date"
               name="check_in"
               value="<?= htmlspecialchars($b['check_in']) ?>"
               min="<?= date('Y-m-d') ?>"
               <?= !$isEditable ? 'disabled' : '' ?>>

        <label>Check-out Date</label>
        <input type="date"
               name="check_out"
               value="<?= htmlspecialchars($b['check_out']) ?>"
               min="<?= date('Y-m-d') ?>"
               <?= !$isEditable ? 'disabled' : '' ?>>

        <label>Number of People</label>
        <input type="number"
               name="number_of_people"
               value="<?= (int)$b['number_of_people'] ?>"
               min="1"
               <?= !$isEditable ? 'disabled' : '' ?>>

        <div class="actions">
            <button class="update-btn" type="submit" <?= !$isEditable ? 'disabled' : '' ?>>
                Update Booking
            </button>
            <a class="cancel-btn" href="my_bookings.php">
                Back
            </a>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$isEditable): ?>
        <div class="error">
            ⚠️ This booking is <b><?= htmlspecialchars($b['status']) ?></b> and cannot be edited.
        </div>
    <?php endif; ?>

</div>
</div>

</body>
</html>
