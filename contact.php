<?php
require "config.php";

$toastMsg = "";
$toastType = ""; // success | error | warning

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $message === "") {
        $toastMsg = "Please fill all required fields.";
        $toastType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $toastMsg = "Please enter a valid email address.";
        $toastType = "warning";

    } else {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $subject, $message]);

        $toastMsg = "Thank you! Your message has been sent successfully.";
        $toastType = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Us | Mr.Traveller</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }

body {
    margin: 0;
    background: #f5f7ff;
    color: #333;
}

/* ================= BACK HOME ================= */
.back-home {
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1500;
    padding: 10px 18px;
    background: white;
    color: #007bff;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    box-shadow: 0 8px 22px rgba(0,0,0,0.15);
}

/* ================= HERO ================= */
.contact-hero {
    height: 50vh;
    background:
        linear-gradient(
            rgba(0,0,0,0.45),
            rgba(0,0,0,0.45)
        ),
        url("assets/img/banner.jpg") center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    backdrop-filter: blur(4px);
}
.contact-hero h1 {
    color: white;
    font-size: 42px;
    text-shadow: 0 6px 20px rgba(0,0,0,0.6);
}

.contact-hero p {
    color: #e0e0e0;
}

/* ================= LAYOUT ================= */
.container {
    max-width: 1200px;
    margin: auto;
    padding: 60px 20px;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.contact-info,
.contact-form {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

/* ================= FORM ================= */
.contact-form input,
.contact-form textarea {
    width: 100%;
    padding: 14px;
    margin-bottom: 16px;
    border-radius: 12px;
    border: 1px solid #ccc;
    font-size: 14px;
}

.contact-form textarea {
    resize: none;
    height: 140px;
}

.contact-form button {
    width: 100%;
    padding: 14px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

/* ================= TOAST ================= */
.toast {
    position: fixed;
    top: 30px;
    right: 30px;
    min-width: 280px;
    padding: 14px 20px;
    border-radius: 14px;
    color: white;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 12px 30px rgba(0,0,0,0.25);
    z-index: 3000;
    animation: slideIn 0.4s ease, fadeOut 0.5s ease 3s forwards;
}

/* Toast colors */
.toast.success { background: #22c55e; }
.toast.error   { background: #ef4444; }
.toast.warning { background: #f59e0b; }

/* -------- Footer -------- */
.footer {
    margin-top: 50px;
    padding: 25px;
    text-align: center;
    background: #222;
    color: #ccc;
    font-size: 14px;
}

/* Animations */
@keyframes slideIn {
    from { opacity: 0; transform: translateX(40px); }
    to   { opacity: 1; transform: translateX(0); }
}

@keyframes fadeOut {
    to { opacity: 0; transform: translateY(-20px); }
}

/* ================= RESPONSIVE ================= */
@media (max-width: 900px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .toast {
        left: 15px;
        right: 15px;
        text-align: center;
    }
}
</style>
</head>

<body>

<a href="home.php" class="back-home">← Home</a>

<?php if ($toastMsg): ?>
<div id="toast" class="toast <?= $toastType ?>">
    <?php if ($toastType === "success") echo "✅"; ?>
    <?php if ($toastType === "error") echo "❌"; ?>
    <?php if ($toastType === "warning") echo "⚠️"; ?>
    <?= htmlspecialchars($toastMsg) ?>
</div>
<?php endif; ?>

<section class="contact-hero">
    <div>
        <h1>Contact Us</h1>
        <p>We’re here to help you plan your perfect journey</p>
    </div>
</section>

<div class="container">
<div class="contact-grid">

<!-- INFO -->
<div class="contact-info">
    <h2>Get in Touch</h2>
    <p>📍 Colombo, Sri Lanka</p>
    <p>📧 support@mrtraveller.com</p>
    <p>📞 +94 77 123 4567</p>
    <p>⏰ Mon – Fri | 9.00 AM – 6.00 PM</p>
</div>

<!-- FORM -->
<div class="contact-form">
    <h2>Send a Message</h2>

    <form method="POST">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <input type="text" name="subject" placeholder="Subject">
        <textarea name="message" placeholder="Your Message" required></textarea>
        <button type="submit">Send Message</button>
    </form>
</div>

</div>
</div>
<footer class="footer">
    <p>&copy; 2025 Mr.Traveller. All Rights Reserved.</p>
</footer>

<script>
setTimeout(() => {
    const toast = document.getElementById("toast");
    if (toast) toast.remove();
}, 3800);
</script>

</body>
</html>
