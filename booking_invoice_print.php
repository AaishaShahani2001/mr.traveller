<?php
session_start();
require "config.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

if (!isset($_GET['id'])) {
    die("Invalid booking");
}

$booking_id = $_GET['id'];

$sql = $conn->prepare("
    SELECT b.*, u.full_name, u.email,
           d.title, d.country, d.city
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
<html>
<head>
<title>Invoice - Mr.Traveller</title>

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
            <p><b>Invoice Date:</b> <?php echo date("Y-m-d"); ?></p>
            <p><b>Booking ID:</b> <?php echo $b['booking_id']; ?></p>
        </div>
    </div>

    <p>
        <b>Customer:</b> <?php echo htmlspecialchars($b['full_name']); ?><br>
        <b>Email:</b> <?php echo htmlspecialchars($b['email']); ?>
    </p>

    <table>
        <tr>
            <th>Package</th>
            <th>Destination</th>
            <th>Travel Date</th>
            <th>People</th>
            <th>Price</th>
        </tr>

        <tr>
            <td><?php echo $b['title']; ?></td>
            <td><?php echo $b['country']." - ".$b['city']; ?></td>
            <td><?php echo $b['travel_date']; ?></td>
            <td><?php echo $b['number_of_people']; ?></td>
            <td>$<?php echo number_format($b['total_amount'], 2); ?></td>
        </tr>
    </table>

    <p class="total">
        Total Amount: $<?php echo number_format($b['total_amount'], 2); ?>
    </p>

    <div class="footer">
        Thank you for booking with Mr.Traveller ✈️ <br>
        We wish you a pleasant journey!
    </div>

    <br>

    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>

</div>

</body>
</html>
