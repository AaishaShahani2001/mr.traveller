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

    $dest_id  = $_POST['dest_id'];
    $name     = $_POST['name'];
    $type     = $_POST['type'];
    $price    = $_POST['price'];
    $rating   = $_POST['rating'];
    $amenities= $_POST['amenities'];

    /* Image Upload */
    $imageName = "";
    if (!empty($_FILES['image']['name'])) {
        $imageName = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "../uploads/" . $imageName
        );
    }

    $insert = $conn->prepare("
        INSERT INTO hotels 
        (dest_id, name, type, price_per_night, rating, amenities, image)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $insert->execute([
        $dest_id, $name, $type, $price, $rating, $amenities, $imageName
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
body { font-family: Arial; background:#f5f7ff; }
.container { max-width:600px; margin:40px auto; background:#fff; padding:25px; border-radius:8px; }
input, select, textarea, button { width:100%; padding:10px; margin:8px 0; }
button { background:#4f46e5; color:#fff; border:none; cursor:pointer; }
.success { color:green; }
</style>
</head>

<body>
<div class="container">
<h2>Add Hotel / Accommodation</h2>

<?php if ($msg): ?>
<p class="success"><?= $msg ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<select name="dest_id" required>
    <option value="">Select Destination</option>
    <?php foreach ($destinations as $d): ?>
        <option value="<?= $d['dest_id'] ?>"><?= $d['title'] ?></option>
    <?php endforeach; ?>
</select>

<input type="text" name="name" placeholder="Hotel Name" required>

<select name="type" required>
    <option value="Hotel">Hotel</option>
    <option value="Resort">Resort</option>
    <option value="Villa">Villa</option>
    <option value="Homestay">Homestay</option>
</select>

<input type="number" name="price" placeholder="Price per night" required>

<input type="number" step="0.1" name="rating" placeholder="Rating (4.5)">

<textarea name="amenities" placeholder="Amenities (WiFi, Pool, AC)"></textarea>

<input type="file" name="image">

<button type="submit">Add Hotel</button>

</form>
</div>
</body>
</html>
