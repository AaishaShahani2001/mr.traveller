<?php
session_start();
require "config.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid destination ID!");
}

$dest_id = (int)$_GET['id'];

/* Fetch destination */
$sql = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$sql->execute([$dest_id]);
$dest = $sql->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    die("Destination not found!");
}

/* Fetch hotels */
$hotelStmt = $conn->prepare("SELECT * FROM hotels WHERE dest_id = ?");
$hotelStmt->execute([$dest_id]);
$hotels = $hotelStmt->fetchAll(PDO::FETCH_ASSOC);

/* Fetch travel facilities */
$facilityStmt = $conn->prepare("SELECT * FROM travel_facilities WHERE dest_id = ?");
$facilityStmt->execute([$dest_id]);
$facilities = $facilityStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($dest['title']) ?> | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    margin: 0;
    background: #f5f7ff;
}

/* Container */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 40px 20px;
}

.back-link {
    display: inline-block;
    margin-bottom: 18px;
    font-size: 15px;
    font-weight: 600;
    color: #007bff;
    text-decoration: none;
}

.back-link:hover {
    transform: translateX(-4px);
}

/* Destination Card */
.details-box {
    background: white;
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    display: flex;
    gap: 30px;
    align-items: center;
}

.image-box {
    flex: 1;
}

.image-box img {
    width: 100%;
    height: 420px;
    object-fit: contain;
    background: #f1f3ff;
    border-radius: 16px;
}

.info-box {
    flex: 1.2;
}

.info-box h2 {
    font-size: 34px;
    margin-bottom: 8px;
}

.location {
    font-weight: 600;
    color: #555;
    margin-bottom: 14px;
}

.price {
    font-size: 26px;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 10px;
}

.duration {
    font-size: 17px;
    margin-bottom: 12px;
}

.desc {
    line-height: 1.7;
    color: #444;
}

/* Buttons */
.btn-row {
    margin-top: 28px;
    display: flex;
    gap: 16px;
}

.btn {
    padding: 14px 30px;
    font-size: 16px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    border: none;
}

.book-btn {
    background: #007bff;
    color: white;
}

.wish-btn {
    background: #ff4757;
    color: white;
}

/* Sections */
.section {
    margin-top: 60px;
}

.section h3 {
    font-size: 26px;
    margin-bottom: 20px;
}

/* Cards */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
}

.card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    overflow: hidden;
}

.card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.card-body {
    padding: 16px;
}

.card-body h4 {
    margin: 0 0 6px;
    font-size: 18px;
}

.card-body p {
    font-size: 14px;
    color: #555;
}

.badge {
    background: #007bff;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    display: inline-block;
    margin-bottom: 6px;
}

.price-tag {
    font-weight: bold;
    color: #007bff;
}

/* Responsive */
@media (max-width: 900px) {
    .details-box {
        flex-direction: column;
        text-align: center;
    }
    .image-box img {
        height: 300px;
    }
}
</style>
</head>

<body>

<div class="container">
<a href="destinations.php" class="back-link">← Back to Destinations</a>

<!-- DESTINATION DETAILS -->
<div class="details-box">
    <div class="image-box">
        <img src="uploads/<?= htmlspecialchars($dest['image']) ?>">
    </div>

    <div class="info-box">
        <h2><?= htmlspecialchars($dest['title']) ?></h2>
        <p class="location"><?= htmlspecialchars($dest['country']) ?> — <?= htmlspecialchars($dest['city']) ?></p>
        <p class="price">$<?= number_format($dest['price'], 2) ?></p>
        <p class="duration">Duration: <?= htmlspecialchars($dest['duration']) ?></p>
        <p class="desc"><?= nl2br(htmlspecialchars($dest['description'])) ?></p>

        <div class="btn-row">
            <a class="btn book-btn" href="booking.php?id=<?= $dest_id ?>">Book Now</a>
            <button class="btn wish-btn">Add to Wishlist</button>
        </div>
    </div>
</div>

<!-- HOTELS -->
<div class="section">
<h3>🏨 Available Accommodations</h3>

<?php if ($hotels): ?>
<div class="card-grid">
<?php foreach ($hotels as $h): ?>
<div class="card">
    <img src="uploads/<?= htmlspecialchars($h['image']) ?>">
    <div class="card-body">
        <span class="badge"><?= htmlspecialchars($h['type']) ?></span>
        <h4><?= htmlspecialchars($h['name']) ?></h4>
        <p>⭐ Rating: <?= $h['rating'] ?: 'N/A' ?></p>
        <p class="price-tag">$<?= number_format($h['price_per_night'], 2) ?> / night</p>
        <p><?= htmlspecialchars($h['amenities']) ?></p>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p>No accommodations available.</p>
<?php endif; ?>
</div>

<!-- TRAVEL FACILITIES -->
<div class="section">
<h3>🚗 Travel Facilities</h3>

<?php if ($facilities): ?>
<div class="card-grid">
<?php foreach ($facilities as $f): ?>
<div class="card">
    <div class="card-body">
        <span class="badge"><?= htmlspecialchars($f['transport_type']) ?></span>
        <h4><?= htmlspecialchars($f['provider_name']) ?></h4>
        <p>Duration: <?= htmlspecialchars($f['duration']) ?></p>
        <p class="price-tag">$<?= number_format($f['price'], 2) ?></p>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p>No travel facilities available.</p>
<?php endif; ?>
</div>

</div>
</body>
</html>
