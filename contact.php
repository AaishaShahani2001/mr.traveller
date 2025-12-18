<?php
require "config.php";

$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);

    if ($name && $email && $message) {
        $stmt = $conn->prepare("
            INSERT INTO contact_messages (name, email, subject, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $subject, $message]);

        $success = "Thank you! Your message has been sent successfully.";
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
/* ---------- SAME STYLES (unchanged) ---------- */
* { box-sizing: border-box; font-family: "Segoe UI", Arial, sans-serif; }
body { margin: 0; background: #f5f7ff; color: #333; }

/* Back button */
.back-home {
    position: fixed;
    top: 20px;
    left: 20px;
    padding: 10px 18px;
    background: white;
    color: #007bff;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    box-shadow: 0 8px 22px rgba(0,0,0,0.15);
}

/* Hero */
.contact-hero {
    height: 50vh;
    background: linear-gradient(rgba(0,0,0,0.55),rgba(0,0,0,0.55)),
    url("assets/img/contact.jpg") center/cover no-repeat;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.contact-hero h1 { color: white; font-size: 42px; }
.contact-hero p { color: #ddd; }

/* Layout */
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
.contact-info, .contact-form {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
}

/* Form */
.contact-form input,
.contact-form textarea {
    width: 100%;
    padding: 14px;
    margin-bottom: 16px;
    border-radius: 12px;
    border: 1px solid #ccc;
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

/* Success message */
.success {
    background: #e6fffa;
    color: #065f46;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 15px;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 900px) {
    .contact-grid { grid-template-columns: 1fr; }
}
</style>
</head>

<body>

<a href="home.php" class="back-home">← Home</a>

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
</div>

<!-- FORM -->
<div class="contact-form">
    <h2>Send a Message</h2>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

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

</body>
</html>
