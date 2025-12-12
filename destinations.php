<?php
require "config.php";

// Fetch all destinations
$sql = $conn->prepare("SELECT * FROM destinations ORDER BY dest_id DESC");
$sql->execute();
$destinations = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Destinations - Mr.Traveller</title>
    <style>
        body { font-family: Arial; background:#f5f7ff; }
        .title { text-align:center; margin-top:30px; font-size:32px; }

        .grid {
            width:90%;
            display:flex;
            margin:40px auto;
            gap:25px;
            flex-wrap:wrap;
            justify-content:center;
        }
        .card {
            width:300px;
            background:white;
            border-radius:10px;
            overflow:hidden;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
            text-align:center;
        }
        .card img {
            width:100%;
            height:200px;
            object-fit:cover;
        }
        .card h3 { margin:10px 0 5px; }
        .card p { color:#555; }
        .btn {
            display:inline-block;
            margin:10px 0 20px;
            padding:8px 20px;
            background:#007bff;
            color:white;
            border-radius:6px;
            text-decoration:none;
        }
    </style>
</head>

<body>

<h2 class="title">Explore Our Travel Packages</h2>

<div class="grid">

<?php foreach ($destinations as $dest): ?>
    <div class="card">
        <img src="uploads/<?php echo $dest['image']; ?>" alt="Destination Image">

        <h3><?php echo $dest['title']; ?></h3>
        <p><?php echo $dest['country']; ?> - <?php echo $dest['city']; ?></p>
        <p><b>Price:</b> $<?php echo $dest['price']; ?></p>
        <p><b>Duration:</b> <?php echo $dest['duration']; ?></p>

        <a class="btn" href="view_destination.php?id=<?php echo $dest['dest_id']; ?>">
            View Package
        </a>
    </div>
<?php endforeach; ?>

</div>

</body>
</html>
