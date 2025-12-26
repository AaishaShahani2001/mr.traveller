<?php
session_start();
require "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

$stmt = $conn->prepare("SELECT dest_id, title FROM destinations");
$stmt->execute();
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dest_id   = $_POST['dest_id'];
    $name      = $_POST['name'];
    $type      = $_POST['type'];
    $price     = $_POST['price'];
    $rating    = $_POST['rating'];
    $amenities = $_POST['amenities'];

    $imageName = "";

    if (!empty($_FILES['image']['name'])) {

        // Ensure uploads folder exists
        if (!is_dir("uploads")) {
            mkdir("uploads", 0777, true);
        }

        $imageName = time() . "_" . basename($_FILES['image']['name']);

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "uploads/" . $imageName  
        );
    }

    $conn->prepare("
        INSERT INTO hotels
        (dest_id, name, type, price_per_night, rating, amenities, image)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $dest_id,
        $name,
        $type,
        $price,
        $rating,
        $amenities,
        $imageName
    ]);

    $msg = "Hotel added successfully!";
}
?>


<!DOCTYPE html>
<html>
<head>
<title>Add Hotel | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial; }
body { margin:0; background:#f5f7ff; }

.admin-wrapper { display:flex; min-height:100vh; }

/* Sidebar */
.sidebar {
    width:250px; background:#1f2937; color:white;
    padding-top:30px; position:fixed; height:100%;
}
.sidebar h2 { text-align:center; margin-bottom:30px; }
.sidebar a {
    display:block; padding:14px 22px; color:#e5e7eb;
    text-decoration:none;
}
.sidebar a:hover, .sidebar a.active {
    background:#2563eb; color:white;
}

/* Content */
.content { flex:1; padding:40px; margin-left:250px;}

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
    gap:20px;
}

label { font-weight:600; display:block; margin-top:10px; }
input, select, textarea {
    width:100%; padding:12px; border-radius:10px;
    border:1px solid #ccc;
}

textarea { resize:none; }

button {
    margin-top:25px;
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

.success {
    background:#d4edda; color:#155724;
    padding:12px; border-radius:10px; margin-bottom:20px;
}

@media(max-width:900px){
    .grid { grid-template-columns:1fr; }
    .sidebar { width:100%; }
}
</style>
</head>

<body>
<div class="admin-wrapper">

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">📊 Dashboard</a>
    <a href="admin_manage_destinations.php">📍 Destinations</a>
    <a href="add_destination.php">➕ Add Destination</a>
    <a class="active" href="add_hotel.php">➕ Add Accommodation</a>
    <a href="admin_manage_hotels.php">🏨 Manage Hotels</a>
    <a href="add_travel_facility.php">➕ Add Travel-Facility</a>
    <a href="admin_manage_users.php">👤 Users</a>
    <a href="admin_manage_bookings.php">📅 Bookings</a>
    <a href="admin_manage_contact.php">📩 Messages</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="content">
<h1>Add Hotel</h1>

<div class="card">

<?php if ($msg): ?><div class="success"><?= $msg ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="grid">

<div>
<label>Destination</label>
<select name="dest_id" required>
<option value="">Select Destination</option>
<?php foreach ($destinations as $d): ?>
<option value="<?= $d['dest_id'] ?>"><?= $d['title'] ?></option>
<?php endforeach; ?>
</select>

<label>Hotel Name</label>
<input type="text" name="name" required>

<label>Type</label>
<select name="type">
<option>Hotel</option>
<option>Resort</option>
<option>Villa</option>
<option>Homestay</option>
</select>

<label>Price / Night</label>
<input type="number" name="price" required>
</div>

<div>
<label>Rating</label>
<input type="number" step="0.1" name="rating">

<label>Amenities</label>
<textarea name="amenities" rows="5"></textarea>

<label>Image</label>
<input type="file" name="image">
</div>

</div>

<button>Add Hotel</button>

</form>
</div>
</div>
</div>
</body>
</html>
