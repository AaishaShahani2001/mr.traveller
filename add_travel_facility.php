<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* Fetch destinations */
$stmt = $conn->prepare("SELECT dest_id, title FROM destinations");
$stmt->execute();
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dest_id   = $_POST['dest_id'];
    $type      = $_POST['transport_type'];
    $provider  = $_POST['provider'];
    $price     = $_POST['price'];
    $duration  = $_POST['duration'];

    $insert = $conn->prepare("
        INSERT INTO travel_facilities
        (dest_id, transport_type, provider_name, price, duration)
        VALUES (?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $dest_id, $type, $provider, $price, $duration
    ]);

    $msg = "Travel facility added successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Travel Facility | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body { font-family: Arial; background:#f5f7ff; }
.container { max-width:600px; margin:40px auto; background:#fff; padding:25px; border-radius:8px; }
input, select, button { width:100%; padding:10px; margin:8px 0; }
button { background:#16a34a; color:#fff; border:none; cursor:pointer; }
.success { color:green; }
</style>
</head>

<body>
<div class="container">
<h2>Add Travel Facility</h2>

<?php if ($msg): ?>
<p class="success"><?= $msg ?></p>
<?php endif; ?>

<form method="post">

<select name="dest_id" required>
    <option value="">Select Destination</option>
    <?php foreach ($destinations as $d): ?>
        <option value="<?= $d['dest_id'] ?>"><?= $d['title'] ?></option>
    <?php endforeach; ?>
</select>

<select name="transport_type" required>
    <option value="Flight">Flight</option>
    <option value="Bus">Bus</option>
    <option value="Train">Train</option>
    <option value="Taxi">Taxi</option>
    <option value="Boat">Boat</option>
</select>

<input type="text" name="provider" placeholder="Provider Name">

<input type="number" name="price" placeholder="Price">

<input type="text" name="duration" placeholder="Duration (3 hrs, 45 min)">

<button type="submit">Add Travel Facility</button>

</form>
</div>
</body>
</html>
