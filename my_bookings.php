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
    ORDER BY b.booking_id DESC
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
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }

body {
    margin: 0;
    background: #f5f7ff;
}

.container {
    max-width: 1200px;
    margin: auto;
    padding: 40px 20px;
}

h2 {
    margin-bottom: 20px;
}

/* Toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #2ecc71;
    color: white;
    padding: 14px 22px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: slideIn 0.5s ease;
    z-index: 999;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(40px); }
    to { opacity: 1; transform: translateX(0); }
}

/* Table */
.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    border-radius: 14px;
    overflow: hidden;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #eee;
    text-align: left;
}

th {
    background: #007bff;
    color: white;
    font-weight: 600;
}

tr:hover {
    background: #f1f4ff;
}

/* Status badges */
.status {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    display: inline-block;
}

.pending { background: #fff3cd; color: #856404; }
.confirmed { background: #d4edda; color: #155724; }
.cancelled { background: #f8d7da; color: #721c24; }

/* Cancel button */
.cancel-btn {
    color: #e74c3c;
    font-weight: bold;
    text-decoration: none;
}

.cancel-btn:hover {
    text-decoration: underline;
}

/* Mobile cards */
@media (max-width: 768px) {

    table, thead, tbody, th, td, tr {
        display: block;
    }

    thead {
        display: none;
    }

    tr {
        background: white;
        margin-bottom: 20px;
        padding: 18px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }

    td {
        border: none;
        padding: 6px 0;
    }

    td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #555;
        display: block;
    }
}
</style>
</head>

<body>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'booked'): ?>
    <div class="toast">Booking successful 🎉</div>
<?php endif; ?>

<div class="container">
    <h2>My Bookings</h2>

    <?php if (count($bookings) === 0): ?>
        <p>You have no bookings yet.</p>
    <?php else: ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Package</th>
                    <th>Location</th>
                    <th>Travel Date</th>
                    <th>People</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                    <th>Booked On</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td data-label="Package"><?= htmlspecialchars($b['title']) ?></td>
                    <td data-label="Location"><?= htmlspecialchars($b['country']) ?> - <?= htmlspecialchars($b['city']) ?></td>
                    <td data-label="Travel Date"><?= $b['travel_date'] ?></td>
                    <td data-label="People"><?= $b['number_of_people'] ?></td>
                    <td data-label="Total">$<?= number_format($b['total_amount'],2) ?></td>

                    <td data-label="Status">
                        <span class="status <?= $b['status'] ?>">
                            <?= ucfirst($b['status']) ?>
                        </span>
                    </td>

                    <td data-label="Action">
                        <?php if ($b['status'] === 'pending'): ?>
                            <a class="cancel-btn"
                               href="user_cancel_booking.php?id=<?= $b['booking_id'] ?>"
                               onclick="return confirm('Cancel this booking?')">
                               Cancel
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>

                    <td data-label="Booked On"><?= $b['booking_date'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

</body>
</html>
