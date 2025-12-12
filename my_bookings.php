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
    ORDER BY b.created_at DESC
");
$sql->execute([$user_id]);
$bookings = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings - Mr.Traveller</title>
    <style>
        body { font-family: Arial; background:#f5f7ff; margin:0; padding:0; }
        .container { width:90%; margin:auto; padding:30px 0; }

        h2 { margin-bottom:20px; }

        table {
            width:100%;
            border-collapse:collapse;
            background:white;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding:12px;
            border:1px solid #ddd;
            text-align:left;
        }
        th {
            background:#007bff;
            color:white;
        }

        .status-pending { color:orange; font-weight:bold; }
        .status-confirmed { color:green; font-weight:bold; }
        .status-cancelled { color:red; font-weight:bold; }

        .msg { margin-bottom:10px; color:green; }
    </style>
</head>
<body>

<div class="container">
    <h2>My Bookings</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'booked'): ?>
        <p class="msg">Booking successfully created! 🎉</p>
    <?php endif; ?>

    <?php if (count($bookings) === 0): ?>
        <p>You have no bookings yet.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Package</th>
                <th>Location</th>
                <th>Travel Date</th>
                <th>People</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Booked On</th>
            </tr>

            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo $b['title']; ?></td>
                    <td><?php echo $b['country'] . " - " . $b['city']; ?></td>
                    <td><?php echo $b['travel_date']; ?></td>
                    <td><?php echo $b['number_of_people']; ?></td>
                    <td>$<?php echo $b['total_amount']; ?></td>
                    <td class="status-<?php echo $b['status']; ?>">
                    <?php echo ucfirst($b['status']); ?>
                </td>

                <td>
                <?php if ($b['status'] === 'pending'): ?>
                    <a href="user_cancel_booking.php?id=<?php echo $b['booking_id']; ?>"
                    onclick="return confirm('Cancel this booking?')"
                    style="color:red;font-weight:bold;">
                    Cancel
                    </a>
                <?php else: ?>
                    —
                <?php endif; ?>
                </td>

                    <td><?php echo $b['booking_date']; ?></td>
                </tr>
            <?php endforeach; ?>

        </table>
    <?php endif; ?>
</div>

</body>
</html>
