<?php
require "config.php";

if (!isset($_GET['id'])) {
    die("Destination ID not provided!");
}

$dest_id = $_GET['id'];

// Fetch destination
$sql = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$sql->execute([$dest_id]);
$dest = $sql->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    die("Destination not found!");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $dest['title']; ?> - Mr.Traveller</title>

    <style>
        body { font-family: Arial; background:#f5f7ff; margin:0; padding:0; }
        .container { width:90%; margin:auto; padding:30px 0; }

        .details-box {
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
            display:flex;
            gap:30px;
        }

        .image-box {
            flex:1;
        }
        .image-box img {
            width:100%;
            border-radius:10px;
            height:400px;
            object-fit:cover;
        }

        .info-box {
            flex:1.2;
        }
        h2 { font-size:32px; }
        .price { font-size:22px; color:#007bff; margin:10px 0; }
        .duration { font-size:18px; margin-bottom:10px; }
        .location { font-weight:bold; margin-bottom:15px; }
        .desc { margin-top:15px; line-height:1.6; }

        .btn-row { margin-top:25px; display:flex; gap:20px; }

        .btn {
            padding:12px 25px;
            font-size:16px;
            border-radius:6px;
            text-decoration:none;
            font-weight:bold;
            display:inline-block;
        }

        .book-btn { background:#007bff; color:white; }
        .wish-btn { background:#ff4757; color:white; }

        .book-btn:hover { background:#005fcc; }
        .wish-btn:hover { background:#d83445; }

    </style>
</head>

<body>

<div class="container">

    <div class="details-box">

        <!-- IMAGE -->
        <div class="image-box">
            <img src="uploads/<?php echo $dest['image']; ?>" alt="Image">
        </div>

        <!-- INFO -->
        <div class="info-box">

            <h2><?php echo $dest['title']; ?></h2>

            <p class="location">
                <?php echo $dest['country']; ?> — <?php echo $dest['city']; ?>
            </p>

            <p class="price">$<?php echo $dest['price']; ?></p>

            <p class="duration">
                Duration: <?php echo $dest['duration']; ?>
            </p>

            <p class="desc">
                <?php echo nl2br($dest['description']); ?>
            </p>

            <!-- BUTTONS -->
            <div class="btn-row">
                <a class="btn book-btn" href="booking.php?id=<?php echo $dest['dest_id']; ?>">
                    Book Now
                </a>

                <a class="btn wish-btn" href="wishlist_add.php?id=<?php echo $dest['dest_id']; ?>">
                    Add to Wishlist
                </a>
            </div>

        </div>

    </div>

</div>

</body>
</html>
