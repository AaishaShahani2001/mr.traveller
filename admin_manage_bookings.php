<?php
session_start();
require "config.php";

// Admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Filters
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Base query
$query = "
SELECT b.*, u.full_name, d.title
FROM bookings b
JOIN users u ON b.user_id = u.user_id
JOIN destinations d ON b.dest_id = d.dest_id
WHERE 1
";

$params = [];

// Status filter
if ($status !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $status;
}

// Search filter (user OR package)
if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR d.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY b.booking_id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Bookings</title>
<style>
body { font-family: Arial; background:#f5f6fa; }
.container { width:95%; margin:auto; }

table {
    width:100%; margin-top:20px; border-collapse:collapse;
    background:white; box-shadow:0 2px 10px rgba(0,0,0,0.1);
}
th, td { padding:12px; border:1px solid #ddd; }
th { background:#8e44ad; color:white; }

.status-pending { color:orange; font-weight:bold; }
.status-confirmed { color:green; font-weight:bold; }
.status-cancelled { color:red; font-weight:bold; }

.action {
    padding:6px 12px;
    border-radius:5px;
    color:white;
    text-decoration:none;
    font-size:14px;
}
.approve { background:#27ae60; }
.cancel { background:#c0392b; }

.filters {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:20px;
}

.filters a {
    margin-right:10px;
    text-decoration:none;
    font-weight:bold;
}

.search-box input {
    padding:8px;
    width:220px;
}
.search-box button {
    padding:8px 14px;
    background:#2980b9;
    color:white;
    border:none;
    cursor:pointer;
}

.msg { text-align:center; color:green; font-weight:bold; margin-top:10px; }
</style>
</head>

<body>

<div class="container">

<h2 style="text-align:center;">Manage Bookings</h2>

<!-- STATUS FILTERS -->
<div class="filters">
    <div>
        <a href="admin_manage_bookings.php?status=all">All</a>
        <a href="admin_manage_bookings.php?status=pending">Pending</a>
        <a href="admin_manage_bookings.php?status=confirmed">Confirmed</a>
        <a href="admin_manage_bookings.php?status=cancelled">Cancelled</a>
    </div>

    <!-- SEARCH -->
    <form method="GET" class="search-box">
        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
        <input type="text" name="search" placeholder="Search user or package"
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg']=='updated'): ?>
    <p class="msg">Booking status updated successfully!</p>
<?php endif; ?>

<table>
<tr>
    <th>User</th>
    <th>Package</th>
    <th>Travel Date</th>
    <th>People</th>
    <th>Total</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php if (count($bookings) === 0): ?>
<tr>
    <td colspan="7" style="text-align:center;">No bookings found.</td>
</tr>
<?php endif; ?>

<?php foreach ($bookings as $b): ?>
<tr>
    <td><?php echo htmlspecialchars($b['full_name']); ?></td>
    <td><?php echo htmlspecialchars($b['title']); ?></td>
    <td><?php echo $b['travel_date']; ?></td>
    <td><?php echo $b['number_of_people']; ?></td>
    <td>$<?php echo $b['total_amount']; ?></td>

    <td class="status-<?php echo $b['status']; ?>">
        <?php echo ucfirst($b['status']); ?>
    </td>

    <td>
        <?php if ($b['status'] === 'pending'): ?>
            <a class="action approve"
               href="admin_update_booking.php?id=<?php echo $b['booking_id']; ?>&status=confirmed"
               onclick="return confirm('Approve this booking?')">
               Approve
            </a>

            <a class="action cancel"
               href="admin_update_booking.php?id=<?php echo $b['booking_id']; ?>&status=cancelled"
               onclick="return confirm('Cancel this booking?')">
               Cancel
            </a>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>

</table>

</div>

</body>
</html>
