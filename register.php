<?php
require "config.php";

// When form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // check if email exists
    $check = $conn->prepare("SELECT email FROM users WHERE email=?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $error = "Email already registered!";
    } else {
        $sql = $conn->prepare("INSERT INTO users(full_name,email,password) VALUES(?,?,?)");
        $sql->execute([$name, $email, $password]);

        header("Location: login.php?msg=registered");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body { font-family: Arial; background: #eef3ff; display:flex; justify-content:center; align-items:center; height:100vh; }
        .box { width:350px; background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.2); }
        input, button { width:100%; padding:10px; margin:8px 0; }
        button { background:#0066ff; color:white; border:none; cursor:pointer; }
        button:hover { background:#0046b9; }
        .error { color:red; }
    </style>
</head>

<body>
<div class="box">
    <h2>Create Account</h2>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>

        <input type="email" name="email" placeholder="Email Address" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Register</button>

        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
</div>
</body>
</html>
