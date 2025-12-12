<?php
session_start();
require "config.php";

// Admin protection
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Validate id
if (!isset($_GET['id'])) {
    die("Destination ID missing!");
}

$id = $_GET['id'];

// Fetch current destination
$stmt = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$stmt->execute([$id]);
$dest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    die("Destination not found!");
}

$error = "";

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title']);
    $country = trim($_POST['country']);
    $city = trim($_POST['city']);
    $price = trim($_POST['price']);
    $duration = trim($_POST['duration']);
    $description = trim($_POST['description']);

    // Keep old image by default
    $image_name = $dest['image'];

    // If new image uploaded
    if (!empty($_FILES['image']['name'])) {
        $new_image = time() . "_" . basename($_FILES['image']['name']);
        $tmp = $_FILES['image']['tmp_name'];

        // Upload new image
        if (move_uploaded_file($tmp, "uploads/" . $new_image)) {

            // Delete old image file
            if (!empty($dest['image']) && file_exists("uploads/" . $dest['image'])) {
                unlink("uploads/" . $dest['image']);
            }

            $image_name = $new_image;
        } else {
            $error = "Image upload failed!";
        }
    }

    // If no image upload error, update DB
    if ($error === "") {
        $update = $conn->prepare("
            UPDATE destinations 
            SET title=?, country=?, city=?, price=?, duration=?, description=?, image=?
            WHERE dest_id=?
        ");

        $update->execute([
            $title,
            $country,
            $city,
            $price,
            $duration,
            $description,
            $image_name,
            $id
        ]);

        header("Location: admin_manage_destinations.php?msg=updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Destination</title>
    <style>
        body { font-family: Arial; background:#eef3ff; padding:40px; }
        .box {
            width:520px; margin:auto; background:white; padding:25px;
            border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.15);
        }
        h2 { margin-bottom:10px; }
        label { font-weight:bold; display:block; margin-top:10px; }
        input, textarea { width:100%; padding:10px; margin-top:6px; }
        textarea { resize:vertical; }
        .row { display:flex; gap:10px; }
        .row > div { flex:1; }

        .img-preview {
            width:100%;
            height:220px;
            object-fit:cover;
            border-radius:8px;
            margin:10px 0;
            border:1px solid #ddd;
        }

        button {
            width:100%; padding:12px; margin-top:15px;
            background:#27ae60; color:white; border:none;
            border-radius:6px; font-weight:bold; cursor:pointer;
        }
        button:hover { background:#1f8b4d; }
        .back {
            display:inline-block; margin-top:15px;
            text-decoration:none; color:#007bff;
        }
        .error { color:red; margin-top:10px; }
    </style>
</head>

<body>

<div class="box">
    <h2>Edit Destination</h2>
    <p><b>ID:</b> <?php echo $dest['dest_id']; ?></p>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($dest['title']); ?>" required>

        <div class="row">
            <div>
                <label>Country</label>
                <input type="text" name="country" value="<?php echo htmlspecialchars($dest['country']); ?>">
            </div>
            <div>
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($dest['city']); ?>">
            </div>
        </div>

        <div class="row">
            <div>
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($dest['price']); ?>" required>
            </div>
            <div>
                <label>Duration</label>
                <input type="text" name="duration" value="<?php echo htmlspecialchars($dest['duration']); ?>">
            </div>
        </div>

        <label>Description</label>
        <textarea name="description" rows="5"><?php echo htmlspecialchars($dest['description']); ?></textarea>

        <label>Current Image</label>
        <?php if (!empty($dest['image'])): ?>
            <img class="img-preview" src="uploads/<?php echo $dest['image']; ?>" alt="Current image">
        <?php else: ?>
            <p>No image uploaded.</p>
        <?php endif; ?>

        <label>Change Image (optional)</label>
        <input type="file" name="image" accept="image/*">

        <button type="submit">Update Destination</button>

        <a class="back" href="admin_manage_destinations.php">← Back to Manage Destinations</a>
    </form>
</div>

</body>
</html>
