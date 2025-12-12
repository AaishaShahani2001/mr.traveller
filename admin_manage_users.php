<?php
session_start();
require "config.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$sql = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
$users = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Users</title>
<style>
table { width:90%; margin:30px auto; border-collapse:collapse; background:white; }
th, td { padding:12px; border:1px solid #ddd; }
th { background:#34495e; color:white; }
</style>
</head>

<body>

<h2 style="text-align:center;">Manage Users</h2>

<table>
<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Role</th>
    <th>Created At</th>
</tr>

<?php foreach($users as $u): ?>
<tr>
    <td><?php echo $u['full_name']; ?></td>
    <td><?php echo $u['email']; ?></td>
    <td><?php echo $u['role']; ?></td>
    <td><?php echo $u['created_at']; ?></td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
