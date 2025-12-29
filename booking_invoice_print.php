<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid booking");
}

$booking_id = (int)$_GET['id'];

$sql = $conn->prepare("
    SELECT 
        b.*, 
        u.full_name, 
        u.email,
        d.title, 
        d.country, 
        d.city,
        DATEDIFF(b.check_out, b.check_in) AS nights
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN destinations d ON b.dest_id = d.dest_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$sql->execute([$booking_id, $_SESSION['user_id']]);
$b = $sql->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    die("Booking not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice | Mr.Traveller</title>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 40px;
    background: white;
}

.invoice-box {
    max-width: 800px;
    margin: auto;
    border: 1px solid #ddd;
    padding: 30px;
}

.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.header h2 span {
    color: #007bff;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
}

th {
    background: #f2f2f2;
}

.total {
    font-size: 20px;
    font-weight: bold;
    text-align: right;
    margin-top: 20px;
}

.footer {
    margin-top: 40px;
    text-align: center;
    color: #666;
    font-size: 14px;
}

/* Hide buttons while printing */
@media print {
    .print-btn {
        display: none;
    }
}
</style>
</head>

<body>

<div class="invoice-box">

    <div class="header">
        <div>
            <h2>Mr.<span>Traveller</span></h2>
            <p>Email: support@mrtraveller.com</p>
        </div>
        <div>
            <p><b>Invoice Date:</b> <?= date("Y-m-d"); ?></p>
            <p><b>Booking ID:</b> <?= $b['booking_id']; ?></p>
        </div>
    </div>

    <p>
        <b>Customer:</b> <?= htmlspecialchars($b['full_name']); ?><br>
        <b>Email:</b> <?= htmlspecialchars($b['email']); ?>
    </p>

    <table>
        <tr>
            <th>Package</th>
            <th>Destination</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Nights</th>
            <th>People</th>
            <th>Total</th>
        </tr>

        <tr>
            <td><?= htmlspecialchars($b['title']); ?></td>
            <td><?= htmlspecialchars($b['country']." - ".$b['city']); ?></td>
            <td><?= $b['check_in']; ?></td>
            <td><?= $b['check_out']; ?></td>
            <td><?= $b['nights']; ?></td>
            <td><?= $b['number_of_people']; ?></td>
            <td>$<?= number_format($b['total_amount']); ?></td>
        </tr>
    </table>

    <p class="total">
        Total Amount: $<?= number_format($b['total_amount']); ?>
    </p>

    <div class="footer">
        Thank you for booking with Mr.Traveller ✈️ <br>
        We wish you a pleasant journey!
    </div>

    <br>

    <button class="print-btn" onclick="window.print()">
        🖨️ Print / Save as PDF
    </button>

</div>

</body>
</html>
