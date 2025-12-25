<?php
session_start();
require "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid facility ID");
}

$facility_id = (int)$_GET['id'];

/* Fetch destinations */
$destStmt = $conn->prepare("SELECT dest_id, title FROM destinations");
$destStmt->execute();
$destinations = $destStmt->fetchAll(PDO::FETCH_ASSOC);

/* Fetch facility */
$stmt = $conn->prepare("SELECT * FROM travel_facilities WHERE facility_id = ?");
$stmt->execute([$facility_id]);
$facility = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$facility) {
    die("Travel facility not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dest_id  = $_POST['dest_id'];
    $type     = $_POST['transport_type'];
    $provider = $_POST['provider'];
    $price    = $_POST['price'];
    $duration = $_POST['duration'];

    $update = $conn->prepare("
        UPDATE travel_facilities SET
            dest_id = ?,
            transport_type = ?,
            provider_name = ?,
            price = ?,
            duration = ?
        WHERE facility_id = ?
    ");

    $update->execute([$dest_id, $type, $provider, $price, $duration, $facility_id]);

    header("Location: admin_manage_travel_facilities.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Travel Facility | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial; }
body { margin:0; background:#f5f7ff; }

/* Sidebar */
.sidebar{
    width:250px; background:#1f2937; color:#fff;
    padding-top:30px; position:fixed; inset:0 auto 0 0;
}
.sidebar h2{ text-align:center; margin-bottom:30px; }
.sidebar a{ display:block; padding:14px 22px; color:#e5e7eb; text-decoration:none; }
.sidebar a:hover, .sidebar a.active{ background:#2563eb; color:#fff; }

/* Content */
.content{
    margin-left:250px;
    padding:40px;
    min-height:100vh;
}
.card{
    background:#fff;
    padding:30px;
    border-radius:18px;
    box-shadow:0 20px 50px rgba(0,0,0,.15);
    max-width:900px;
}
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
}
label{ font-weight:600; display:block; margin-top:12px; }
input, select{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:14px;
}
button{
    margin-top:28px;
    width:100%;
    padding:14px;
    border-radius:30px;
    border:none;
    background:#007bff;
    color:#fff;
    font-weight:bold;
    font-size:16px;
    cursor:pointer;
}
button:hover{ background:#005fcc; }

@media(max-width:900px){
    .sidebar{ position:relative; width:100%; }
    .content{ margin-left:0; padding:24px; }
    .grid{ grid-template-columns:1fr; }
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="add_travel_facility.php">➕ Add Travel Facility</a>
    <a class="active" href="admin_manage_travel_facilities.php">🚗 Manage Travel Facilities</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="content">
    <h1>Edit Travel Facility</h1>

    <div class="card">
        <form method="post">

            <div class="grid">
                <div>
                    <label>Destination</label>
                    <select name="dest_id" required>
                        <?php foreach ($destinations as $d): ?>
                        <option value="<?= $d['dest_id'] ?>"
                            <?= ($d['dest_id'] == $facility['dest_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['title']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Transport Type</label>
                    <select name="transport_type" required>
                        <?php foreach (['Flight','Bus','Train','Taxi','Boat'] as $t): ?>
                        <option value="<?= $t ?>" <?= ($facility['transport_type'] === $t) ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Provider Name</label>
                    <input type="text" name="provider" value="<?= htmlspecialchars($facility['provider_name']) ?>">

                    <label>Price</label>
                    <input type="number" name="price" value="<?= htmlspecialchars($facility['price']) ?>">

                    <label>Duration</label>
                    <input type="text" name="duration" value="<?= htmlspecialchars($facility['duration']) ?>">
                </div>
            </div>

            <button type="submit">Update Travel Facility</button>
        </form>
    </div>
</div>

</body>
</html>
