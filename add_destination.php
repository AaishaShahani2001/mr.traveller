<?php
require "config.php";

$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = $_POST['title'];
    $country = $_POST['country'];
    $city = $_POST['city'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $description = $_POST['description'];

    // IMAGE UPLOAD
    $image_name = "";
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name);
    }

    // INSERT
    $sql = $conn->prepare("
        INSERT INTO destinations 
        (title, country, city, price, image, duration, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $sql->execute([
        $title, $country, $city, $price, $image_name, $duration, $description
    ]);

    $success = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Destination | Admin</title>

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: linear-gradient(135deg, #eef3ff, #dbe6ff);
    padding: 30px;
}

/* Back Button */
.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    text-decoration: none;
    background: #444;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
}

/* Card */
.box {
    max-width: 900px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.box h2 {
    text-align: center;
    margin-bottom: 25px;
}

/* Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.full {
    grid-column: 1 / 3;
}

label {
    font-size: 14px;
    font-weight: 600;
    color: #555;
}

input,
textarea {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

/* Button */
button {
    margin-top: 20px;
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #007bff, #005fcc);
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

/* Toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 14px 22px;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.5s ease;
    z-index: 999;
}

.toast.show {
    opacity: 1;
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    .full {
        grid-column: 1;
    }
}
</style>
</head>

<body>

<a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>

<div class="box">
    <h2>Add New Destination</h2>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-grid">

            <div>
                <label>Package Title</label>
                <input type="text" name="title" required>
            </div>

            <div>
                <label>Country</label>
                <input type="text" name="country">
            </div>

            <div>
                <label>City</label>
                <input type="text" name="city">
            </div>

            <div>
                <label>Price</label>
                <input type="number" step="0.01" name="price" required>
            </div>

            <div>
                <label>Duration</label>
                <input type="text" name="duration">
            </div>

            <div>
                <label>Upload Image</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="full">
                <label>Description</label>
                <textarea name="description" rows="4"></textarea>
            </div>

        </div>

        <button type="submit">Add Destination</button>
    </form>
</div>

<!-- SUCCESS TOAST -->
<?php if ($success): ?>
<div class="toast" id="toast">Destination added successfully ✔</div>

<script>
const toast = document.getElementById("toast");
setTimeout(() => toast.classList.add("show"), 200);
setTimeout(() => toast.classList.remove("show"), 3200);
</script>
<?php endif; ?>

</body>
</html>
