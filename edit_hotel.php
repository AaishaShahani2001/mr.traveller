<?php
session_start();
require "config.php";

/* ---------- Admin Protection ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid hotel ID");
}

$hotel_id = (int)$_GET['id'];

/* Fetch destinations */
$destStmt = $conn->prepare("SELECT dest_id, title FROM destinations");
$destStmt->execute();
$destinations = $destStmt->fetchAll(PDO::FETCH_ASSOC);

/* Fetch hotel */
$stmt = $conn->prepare("SELECT * FROM hotels WHERE hotel_id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    die("Hotel not found");
}

$msg = "";

/* Update hotel */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dest_id   = $_POST['dest_id'];
    $name      = $_POST['name'];
    $type      = $_POST['type'];
    $price     = $_POST['price'];
    $rating    = $_POST['rating'];
    $amenities = $_POST['amenities'];

    $imageName = $hotel['image'];

    /* Image replace (optional) */
    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $imageName);
    }

    $update = $conn->prepare("
        UPDATE hotels SET
            dest_id = ?,
            name = ?,
            type = ?,
            price_per_night = ?,
            rating = ?,
            amenities = ?,
            image = ?
        WHERE hotel_id = ?
    ");

    $update->execute([
        $dest_id, $name, $type, $price,
        $rating, $amenities, $imageName, $hotel_id
    ]);

    header("Location: admin_manage_hotels.php?msg=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Hotel | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial; }
body { margin:0; background:#f5f7ff; }

/* ===== SIDEBAR ===== */
.sidebar {
    width:250px;
    background:#1f2937;
    color:white;
    padding-top:30px;
    position:fixed;
    inset:0 auto 0 0;
}
.sidebar h2 { text-align:center; margin-bottom:30px; }
.sidebar a {
    display:block;
    padding:14px 22px;
    color:#e5e7eb;
    text-decoration:none;
}
.sidebar a:hover,
.sidebar a.active {
    background:#2563eb;
    color:white;
}

/* ===== CONTENT ===== */
.content {
    margin-left:250px;
    padding:40px;
    min-height:100vh;
}

/* ===== CARD ===== */
.card {
    background:white;
    padding:30px;
    border-radius:18px;
    box-shadow:0 20px 50px rgba(0,0,0,.15);
    max-width:1000px;
}

.grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:24px;
}

label { font-weight:600; margin-top:12px; display:block; }
input, select, textarea {
    width:100%; padding:12px;
    border-radius:10px; border:1px solid #ccc;
}

textarea { resize:none; }

.current-img {
    margin-top:10px;
    width:120px;
    height:80px;
    object-fit:cover;
    border-radius:10px;
    border:1px solid #ddd;
}

button {
    margin-top:30px;
    width:100%;
    padding:14px;
    border-radius:30px;
    border:none;
    background:#007bff;
    color:white;
    font-weight:bold;
    font-size:16px;
    cursor:pointer;
}

@media(max-width:900px){
    .sidebar { position:relative; width:100%; }
    .content { margin-left:0; }
    .grid { grid-template-columns:1fr; }
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="add_hotel.php" class="active">🏨 Accommodation</a>
    <a href="manage_hotels.php">🛠 Manage Hotels</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="content">
<h1>Edit Hotel</h1>

<div class="card">
<form method="post" enctype="multipart/form-data">

<div class="grid">

<div>
<label>Destination</label>
<select name="dest_id" required>
<?php foreach ($destinations as $d): ?>
<option value="<?= $d['dest_id'] ?>"
<?= $d['dest_id']==$hotel['dest_id'] ? 'selected' : '' ?>>
<?= htmlspecialchars($d['title']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Hotel Name</label>
<input type="text" name="name" value="<?= htmlspecialchars($hotel['name']) ?>" required>

<label>Type</label>
<select name="type">
<?php foreach (['Hotel','Resort','Villa','Homestay'] as $t): ?>
<option <?= $hotel['type']===$t ? 'selected' : '' ?>><?= $t ?></option>
<?php endforeach; ?>
</select>

<label>Price / Night</label>
<input type="number" name="price" value="<?= $hotel['price_per_night'] ?>" required>
</div>

<div>
<label>Rating</label>
<input type="number" step="0.1" name="rating" value="<?= $hotel['rating'] ?>">

<label>Amenities</label>
<textarea name="amenities" rows="5"><?= htmlspecialchars($hotel['amenities']) ?></textarea>

<label>Replace Image</label>
<input type="file" name="image">

<img src="../uploads/<?= htmlspecialchars($hotel['image']) ?>" class="current-img">
</div>

</div>

<button>Update Hotel</button>

</form>
</div>
</div>

</body>
</html>
