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
    transition: transform 0.3s, color 0.3s;
}

.back-link:hover {
    color: #005fcc;
    transform: translateX(-4px);
}

/* Card */
.details-box {
    background: white;
    padding: 24px;
    border-radius: 18px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    display: flex;
    gap: 30px;
    align-items: center;
}

/* Image */
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

/* Info */
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
    color: #444;
}

.desc {
    line-height: 1.7;
    color: #444;
    margin-top: 15px;
}

/* Buttons */
.btn-row {
    margin-top: 28px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.btn {
    padding: 14px 30px;
    font-size: 16px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    display: inline-block;
    transition: transform 0.3s, box-shadow 0.3s;
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

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
}

/* Toast */
.toast {
    position: fixed;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%) translateY(50px);
    background: #28a745;
    color: white;
    padding: 14px 26px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    opacity: 0;
    transition: 0.5s;
    box-shadow: 0 8px 25px rgba(0,0,0,0.25);
    z-index: 9999;
}
.toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.toast.warning {
    background: #ffc107;
    color: #333;
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
    .btn-row {
        justify-content: center;
    }
}
</style>
</head>

<body>

<div class="container">
    <a href="destinations.php" class="back-link">← Back to Destinations</a>

    <div class="details-box">
        <!-- IMAGE -->
        <div class="image-box">
            <img src="uploads/<?= htmlspecialchars($dest['image']) ?>" alt="Destination Image">
        </div>

        <!-- INFO -->
        <div class="info-box">
            <h2><?= htmlspecialchars($dest['title']) ?></h2>
            <p class="location"><?= htmlspecialchars($dest['country']) ?> — <?= htmlspecialchars($dest['city']) ?></p>
            <p class="price">$<?= number_format($dest['price'], 2) ?></p>
            <p class="duration">Duration: <?= htmlspecialchars($dest['duration']) ?></p>
            <p class="desc"><?= nl2br(htmlspecialchars($dest['description'])) ?></p>

            <div class="btn-row">
                <a class="btn book-btn" href="booking.php?id=<?= $dest['dest_id'] ?>">Book Now</a>
                <button class="btn wish-btn" onclick="addToWishlist(<?= $dest['dest_id'] ?>)">Add to Wishlist</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
function showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast " + (type === "warning" ? "warning" : "");
    toast.classList.add("show");

    setTimeout(() => toast.classList.remove("show"), 3000);
}

function addToWishlist(id) {
    fetch("wishlist_add.php?id=" + id, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showToast(data.message, "success");
        } else if (data.status === "info") {
            showToast(data.message, "warning");
        } else {
            showToast("Something went wrong!", "warning");
        }
    })
    .catch(() => showToast("Error adding to wishlist", "warning"));
}
</script>

</body>
</html>