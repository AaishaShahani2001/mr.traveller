<?php
session_start();
require "config.php";

// When login form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $sql->execute([$email]);
    $user = $sql->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        // Save login data
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: home.php");
        }
        exit;

    } else {
        $error = "Invalid Email or Password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; background:#eef3ff; display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { width:350px; background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.2); }
        input, button { width:100%; padding:10px; margin:8px 0; }
        button { background:#0066ff; color:white; border:none; cursor:pointer; }
        button:hover { background:#0046b9; }
        .success { color:green; }
        .error { color:red; }
    </style>
</head>

<body>
<div class="box">
    <h2>Login</h2>

    <?php
    if (isset($_GET['msg']) && $_GET['msg'] == "registered") {
        echo "<p class='success'>Registration successful! Please login.</p>";
    }
    if (!empty($error)) echo "<p class='error'>$error</p>";
    ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>

        <p>New user? <a href="register.php">Create Account</a></p>
    </form>
</div>
</body>
</html>
