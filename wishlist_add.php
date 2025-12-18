<?php
session_start();
require "config.php";

/* =========================================================
   PART 1: HANDLE AJAX ADD TO WISHLIST (NO REDIRECT)
========================================================= */
if (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header("Content-Type: application/json");

    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Please login first"
        ]);
        exit;
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid destination"
        ]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $dest_id = (int)$_GET['id'];

    /* Check if already in wishlist */
    $check = $conn->prepare(
        "SELECT 1 FROM wishlist WHERE user_id=? AND dest_id=?"
    );
    $check->execute([$user_id, $dest_id]);

    if ($check->rowCount() > 0) {
        echo json_encode([
            "status" => "info",
            "message" => "Already in your wishlist ❤️"
        ]);
        exit;
    }

    /* Insert */
    $insert = $conn->prepare(
        "INSERT INTO wishlist (user_id, dest_id) VALUES (?, ?)"
    );
    $insert->execute([$user_id, $dest_id]);

    echo json_encode([
        "status" => "success",
        "message" => "Added to wishlist ❤️"
    ]);
    exit;
}

/* =========================================================
   PART 2: NORMAL PAGE VIEW (SHOW WISHLIST)
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* Fetch wishlist */
$sql = $conn->prepare("
    SELECT d.dest_id, d.title, d.country, d.city, d.price, d.image
    FROM wishlist w
    JOIN destinations d ON w.dest_id = d.dest_id
    WHERE w.user_id = ?
    ORDER BY w.wishlist_id DESC
");
$sql->execute([$user_id]);
$wishlist = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Wishlist | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing:border-box; font-family:"Segoe UI", Arial, sans-serif; }

body {
    margin:0;
    background:#f5f7ff;
}

/* Back */
.back-home {
    position:fixed;
    top:20px;
    left:20px;
    padding:10px 18px;
    background:white;
    color:#007bff;
    border-radius:30px;
    font-weight:600;
    text-decoration:none;
    box-shadow:0 10px 25px rgba(0,0,0,.2);
}

/* Layout */
.container {
    max-width:1200px;
    margin:auto;
    padding:80px 20px 40px;
}

.page-header {
    text-align:center;
    margin-bottom:40px;
}

/* Grid */
.grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:24px;
}

/* Card */
.card {
    background:white;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 14px 35px rgba(0,0,0,.15);
}

.card img {
    width:100%;
    height:190px;
    object-fit:cover;
}

.card-body {
    padding:18px;
    text-align:center;
}

.price {
    font-size:18px;
    font-weight:bold;
    color:#007bff;
    margin:8px 0 14px;
}

/* Buttons */
.actions {
    display:flex;
    gap:10px;
    justify-content:center;
}

.view-btn {
    padding:10px 22px;
    background:#007bff;
    color:white;
    border-radius:30px;
    text-decoration:none;
}

.remove-btn {
    padding:10px 22px;
    background:#dc3545;
    color:white;
    border-radius:30px;
    border:none;
    cursor:pointer;
}

/* Empty */
.empty {
    text-align:center;
    padding:80px 20px;
    color:#666;
}
</style>
</head>

<body>

<a href="home.php" class="back-home">← Home</a>

<div class="container">

<div class="page-header">
    <h2>My Wishlist ❤️</h2>
    <p>Your saved dream destinations</p>
</div>

<?php if (!$wishlist): ?>
    <div class="empty">💔 No destinations in wishlist</div>
<?php else: ?>

<div class="grid">
<?php foreach ($wishlist as $w): ?>
<div class="card">
    <img src="uploads/<?= htmlspecialchars($w['image']) ?>">

    <div class="card-body">
        <h3><?= htmlspecialchars($w['title']) ?></h3>
        <p><?= htmlspecialchars($w['country']) ?> • <?= htmlspecialchars($w['city']) ?></p>
        <div class="price">$<?= number_format($w['price'],2) ?></div>

        <div class="actions">
            <a class="view-btn"
               href="view_destination.php?id=<?= $w['dest_id'] ?>">
               View
            </a>

            <form action="wishlist_remove.php" method="get">
                <input type="hidden" name="id" value="<?= $w['dest_id'] ?>">
                <button class="remove-btn">Remove</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

</div>

</body>
</html>
