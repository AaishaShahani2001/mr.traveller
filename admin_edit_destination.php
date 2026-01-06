<?php
session_start();
require "config.php";

/* -------- Admin protection -------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit;
}

/* -------- Validate ID -------- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_manage_destinations.php");
    exit;
}

$id = (int)$_GET['id'];

/* -------- Fetch destination -------- */
$stmt = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$stmt->execute([$id]);
$dest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    header("Location: admin_manage_destinations.php");
    exit;
}

$error = "";
$success = false;

/* -------- Handle update -------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title']);
    $country = trim($_POST['country']);
    $city = trim($_POST['city']);
    $price = trim($_POST['price']);
    $duration = trim($_POST['duration']);
    $description = trim($_POST['description']);

    $image_name = $dest['image']; // keep old by default

    /* Image upload */
    if (!empty($_FILES['image']['name'])) {
        $newImage = time() . "_" . basename($_FILES['image']['name']);
        $tmp = $_FILES['image']['tmp_name'];

        if (move_uploaded_file($tmp, "uploads/" . $newImage)) {
            if (!empty($dest['image']) && file_exists("uploads/" . $dest['image'])) {
                unlink("uploads/" . $dest['image']);
            }
            $image_name = $newImage;
        } else {
            $error = "Image upload failed.";
        }
    }

    if ($error === "") {
        $update = $conn->prepare("
            UPDATE destinations 
            SET title=?, country=?, city=?, price=?, duration=?, description=?, image=?
            WHERE dest_id=?
        ");

        $update->execute([
            $title, $country, $city, $price,
            $duration, $description, $image_name, $id
        ]);

        $success = true;

        // Redirect after update
        header("Location: admin_manage_destinations.php?msg=updated");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Destination | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", Arial, sans-serif;
}

body {
    background: linear-gradient(135deg, #eef3ff, #dbe6ff);
    padding: 24px;
}

/* Card */
.card {
    max-width: 1000px;
    margin: auto;
    background: white;
    border-radius: 18px;
    padding: 28px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.18);
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.header h2 {
    margin: 0;
    font-size: 26px;
}

.back-btn {
    text-decoration: none;
    background: #444;
    color: white;
    padding: 10px 16px;
    border-radius: 10px;
    transition: 0.2s;
}
.back-btn:hover { background: #222; }

/* Grid */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.full {
    grid-column: 1 / 3;
}

/* Inputs */
label {
    font-weight: 700;
    font-size: 14px;
    color: #444;
}

input, textarea {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

input:focus, textarea:focus {
    outline: none;
    border-color: #007bff;
}

textarea {
    resize: none;
}

/* Image preview */
.img-preview {
    width: 100%;
    height: 240px;
    object-fit: cover;
    border-radius: 14px;
    margin-top: 10px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}

/* Button */
button {
    width: 100%;
    margin-top: 25px;
    padding: 14px;
    border: none;
    border-radius: 30px;
    background: linear-gradient(135deg, #28a745, #1f8b4d);
    color: white;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(40,167,69,0.5);
}

/* Toast */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 14px 22px;
    border-radius: 12px;
    font-weight: 800;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    opacity: 0;
    transform: translateY(-20px);
    transition: 0.5s;
    z-index: 999;
}
.toast.show {
    opacity: 1;
    transform: translateY(0);
}

.error {
    background: #ffe5e5;
    color: #b00020;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-weight: 700;
}

/* Responsive */
@media (max-width: 800px) {
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

<div class="card">

    <div class="header">
        <h2>Edit Destination</h2>
        <a href="admin_manage_destinations.php" class="back-btn">← Back</a>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-grid">

            <div class="full">
                <label>Package Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($dest['title']); ?>" required>
            </div>

            <div>
                <label>Country</label>
                <input type="text" name="country" value="<?php echo htmlspecialchars($dest['country']); ?>">
            </div>

            <div>
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($dest['city']); ?>">
            </div>

            <div>
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($dest['price']); ?>" required>
            </div>

            <div>
                <label>Duration</label>
                <input type="text" name="duration" value="<?php echo htmlspecialchars($dest['duration']); ?>">
            </div>

            <div class="full">
                <label>Description</label>
                <textarea name="description" rows="4"><?php echo htmlspecialchars($dest['description']); ?></textarea>
            </div>

            <div class="full">
                <label>Current Image</label>
                <?php if (!empty($dest['image'])): ?>
                    <img class="img-preview" src="uploads/<?php echo htmlspecialchars($dest['image']); ?>">
                <?php else: ?>
                    <p>No image uploaded.</p>
                <?php endif; ?>
            </div>

            <div class="full">
                <label>Change Image (optional)</label>
                <input type="file" name="image" accept="image/*">
            </div>

        </div>

        <button type="submit">Update Destination</button>
    </form>

</div>

<?php if ($success): ?>
<div class="toast" id="toast">Destination updated successfully ✔</div>
<script>
const toast = document.getElementById("toast");
setTimeout(() => toast.classList.add("show"), 200);
setTimeout(() => toast.classList.remove("show"), 3200);
</script>
<?php endif; ?>

</body>
</html>
