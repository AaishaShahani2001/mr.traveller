<?php
session_start();
require "config.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if(isset($_GET['msg']) && $_GET['msg']=='updated') 
  echo "<p style='text-align:center;color:green;'>Destination updated successfully!</p>";

$sql = $conn->query("SELECT * FROM destinations ORDER BY dest_id DESC");
$destinations = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Destinations</title>
    <style>
        body { font-family:Arial; background:#f5f6fa; }
        table { width:90%; margin:30px auto; border-collapse:collapse; background:white; }
        th, td { padding:12px; border:1px solid #ddd; text-align:left; }
        th { background:#2980b9; color:white; }
        img { width:80px; height:60px; object-fit:cover; border-radius:6px; }
        a.action { padding:6px 12px; text-decoration:none; color:white; border-radius:6px; }
        .edit { background:#27ae60; }
        .delete { background:#c0392b; }
    </style>
</head>

<body>

<h2 style="text-align:center;">Manage Destinations</h2>

<table>
    <tr>
        <th>Image</th>
        <th>Title</th>
        <th>Location</th>
        <th>Price</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($destinations as $d): ?>
    <tr>
        <td><img src="uploads/<?php echo $d['image']; ?>"></td>
        <td><?php echo $d['title']; ?></td>
        <td><?php echo $d['country'] . " - " . $d['city']; ?></td>
        <td>$<?php echo $d['price']; ?></td>

        <td>
            <a class="action edit" href="admin_edit_destination.php?id=<?php echo $d['dest_id']; ?>">Edit</a>
            <a class="action delete" href="admin_delete_destination.php?id=<?php echo $d['dest_id']; ?>"
               onclick="return confirm('Are you sure you want to delete this?')">
               Delete
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
