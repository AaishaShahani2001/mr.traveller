<?php
require "config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $check = $conn->prepare("SELECT email FROM users WHERE email=?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        header("Location: auth.php?error=exists");
        exit;
    } else {
        $sql = $conn->prepare("INSERT INTO users(full_name,email,password) VALUES(?,?,?)");
        $sql->execute([$name, $email, $password]);

        header("Location: auth.php?success=registered");
        exit;
    }
}
