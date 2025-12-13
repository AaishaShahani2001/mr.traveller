<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login / Register</title>

<style>
* {
  box-sizing: border-box;
  font-family: Arial, sans-serif;
}

body {
  margin: 0;
  height: 100vh;
  background: #eef3ff;
  display: flex;
  justify-content: center;
  align-items: center;
}

.container {
  width: 850px;
  height: 480px;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 15px 40px rgba(0,0,0,0.2);
  display: flex;
}

/* PANELS */
.image-panel {
  width: 50%;
  background: url("assets/img/travel.jpg") center/cover no-repeat;
  order: 1; /* LEFT by default */
}

.form-panel {
  width: 50%;
  padding: 40px;
  order: 2; /* RIGHT by default */
}

/* FORMS */
form {
  display: none;
}

form.active {
  display: block;
}

/* INPUTS */
input {
  width: 100%;
  padding: 12px;
  margin: 10px 0;
}

button {
  width: 100%;
  padding: 12px;
  background: #0066ff;
  border: none;
  color: white;
  cursor: pointer;
}

button:hover {
  background: #0046b9;
}

.switch {
  margin-top: 15px;
  text-align: center;
}

.switch a {
  color: #0066ff;
  font-weight: bold;
  cursor: pointer;
  text-decoration: none;
}

/* REGISTER STATE */
.container.register .image-panel {
  order: 2; /* RIGHT */
}

.container.register .form-panel {
  order: 1; /* LEFT */
}
</style>
</head>

<body>

<div class="container" id="container">

  <!-- IMAGE -->
  <div class="image-panel"></div>

  <!-- FORMS -->
  <div class="form-panel">

    <!-- LOGIN -->
    <form id="loginForm" class="active" action="login.php" method="POST">
      <h2>Login</h2>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>

      <div class="switch">
        New user? <a onclick="showRegister()">Create Account</a>
      </div>
    </form>

    <!-- REGISTER -->
    <form id="registerForm" action="register.php" method="POST">
      <h2>Create Account</h2>
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Register</button>

      <div class="switch">
        Already have an account? <a onclick="showLogin()">Login</a>
      </div>
    </form>

  </div>
</div>

<script>
const container = document.getElementById("container");
const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");

function showRegister() {
  container.classList.add("register");
  loginForm.classList.remove("active");
  registerForm.classList.add("active");
}

function showLogin() {
  container.classList.remove("register");
  registerForm.classList.remove("active");
  loginForm.classList.add("active");
}
</script>

</body>
</html>
