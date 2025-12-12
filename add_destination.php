<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = $_POST['title'];
    $country = $_POST['country'];
    $city = $_POST['city'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $description = $_POST['description'];

    // ---- IMAGE UPLOAD ----
    $image_name = "";
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name);
    }

    // INSERT QUERY
    $sql = $conn->prepare("INSERT INTO destinations 
        (title, country, city, price, image, duration, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    $sql->execute([
        $title, $country, $city, $price, $image_name, $duration, $description
    ]);

    echo "<script>alert('Destination added successfully!'); window.location='add_destination.php';</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Destination</title>
    <style>
        body { font-family: Arial; background:#eef3ff; padding:50px; }
        .box { width:450px; margin:auto; padding:20px; background:white; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.2); }
        input, textarea, button { width:100%; padding:10px; margin:10px 0; }
        button { background:#007bff; color:white; border:none; cursor:pointer; }
        button:hover { background:#005fcc; }
    </style>
</head>

<body>

<div class="box">
    <h2>Add New Destination</h2>

    <form method="POST" enctype="multipart/form-data">

        <input type="text" name="title" placeholder="Package Title" required>

        <input type="text" name="country" placeholder="Country">

        <input type="text" name="city" placeholder="City">

        <input type="number" step="0.01" name="price" placeholder="Price" required>

        <input type="text" name="duration" placeholder="Duration (e.g., 3 Days / 2 Nights)">

        <textarea name="description" placeholder="Description..." rows="4"></textarea>

        <p>Upload Image:</p>
        <input type="file" name="image" accept="image/*">

        <button type="submit">Add Destination</button>
    </form>
</div>

</body>
</html>
