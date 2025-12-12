<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mr.Traveller - Explore the World</title>
    <link rel="stylesheet" href="assets/css/home.css">
</head>

<body>

    <!-- ================= HEADER ================= -->
    <header class="header">
        <div class="container">
            <h2 class="logo">Mr.<span>Traveller</span></h2>

            <nav class="nav">
                <a href="home.php">Home</a>
                <a href="destinations.php">Destinations</a>
                <a href="my_bookings.php">Bookings</a>
                <a href="wishlist_add.php">Wishlist</a>
                <a href="#">Contact</a>
                <a href="logout.php" class="btn">Logout</a>
            </nav>
        </div>
    </header>

    <!-- ================= HERO SECTION ================= -->
    <section class="hero">
        <div class="hero-content">
            <h1>Discover Your Next Adventure</h1>
            <p>Explore breathtaking destinations worldwide and book your dream trip today.</p>
            <a href="destinations.php" class="hero-btn">Explore Destinations</a>
        </div>
    </section>

    <!-- ================= POPULAR DESTINATIONS ================= -->
    <section class="destinations">
        <h2 class="section-title">Popular Destinations</h2>

        <div class="dest-grid">

            <div class="dest-card">
                <img src="assets/img/paris-eiffel.avif" alt="">
                <h3>Paris, France</h3>
                <p>Starting from $899</p>
                <a href="#" class="book-btn">View Package</a>
            </div>

            <div class="dest-card">
                <img src="assets/img/maldives.jpg" alt="">
                <h3>Maldives</h3>
                <p>Starting from $1299</p>
                <a href="#" class="book-btn">View Package</a>
            </div>

            <div class="dest-card">
                <img src="assets/img/singapore.jpg" alt="">
                <h3>Singapore</h3>
                <p>Starting from $599</p>
                <a href="view_destination.php" class="book-btn">View Package</a>
            </div>

        </div>
    </section>

    <!-- ================= CTA SECTION ================= -->
    <section class="cta">
        <h2>Ready to Start Your Journey?</h2>
        <p>Book your next travel destination with Mr.Traveller!</p>
        <a href="booking.php" class="cta-btn">Book Now</a>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="footer">
        <p>&copy; 2025 Mr.Traveller. All Rights Reserved.</p>
    </footer>

</body>
</html>

