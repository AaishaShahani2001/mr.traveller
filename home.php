<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
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
            <a href="contact.php">Contact</a>
            <a href="logout.php" class="btn">Logout</a>
        </nav>
    </div>
</header>

<!-- ================= HERO ================= -->
<section class="hero">
    <div class="hero-content">
        <h1>Discover Your Next Adventure</h1>
        <p>Explore breathtaking destinations worldwide and book your dream trip today.</p>
        <a href="destinations.php" class="hero-btn">Explore Destinations</a>
    </div>
</section>

<!-- ================= WHY CHOOSE US ================= -->
<section class="why-us">
    <h2 class="section-title">Why Choose Us?</h2>
    <div class="why-grid">
        <div class="why-card"><span>🌍</span><h3>Diverse Destinations</h3><p>Explore top global travel spots.</p></div>
        <div class="why-card"><span>💰</span><h3>Value for Money</h3><p>Best prices with premium services.</p></div>
        <div class="why-card"><span>🏝️</span><h3>Beautiful Places</h3><p>Hand-picked scenic locations.</p></div>
        <div class="why-card"><span>❤️</span><h3>Passionate Travel</h3><p>We make every journey special.</p></div>
    </div>
</section>


<!-- ================= TOP DESTINATIONS ================= -->
<section class="top-destinations">
    <h2 class="section-title">Top Destinations</h2>

    <div class="carousel-wrapper">
        <button class="carousel-btn left" onclick="slideLeft()">&#10094;</button>

        <div class="carousel-window">
            <div class="carousel-track" id="carouselTrack">

                <div class="carousel-card">
                    <img src="assets/img/maldives.jpg">
                    <div class="card-info">
                        <h3>Maldives</h3>
                        <p>26+ Activities • 29+ Tours</p>
                    </div>
                </div>

                <div class="carousel-card">
                    <img src="assets/img/greece.jpg">
                    <div class="card-info">
                        <h3>Greece</h3>
                        <p>41+ Activities • 10+ Tours</p>
                    </div>
                </div>

                <div class="carousel-card">
                    <img src="assets/img/brazil.jpg">
                    <div class="card-info">
                        <h3>Brazil</h3>
                        <p>56+ Tours • 12+ Tours</p>
                    </div>
                </div>

                <div class="carousel-card">
                    <img src="assets/img/Paris-eiffel.avif">
                    <div class="card-info">
                        <h3>Paris</h3>
                        <p>48+ Activities • 29 Tours</p>
                    </div>
                </div>

                <div class="carousel-card">
                    <img src="assets/img/singapore.jpg">
                    <div class="card-info">
                        <h3>Singapore</h3>
                        <p>39+ Activities • 42+ Tours</p>
                    </div>
                </div>

                <div class="carousel-card">
                    <img src="assets/img/italy.webp">
                    <div class="card-info">
                        <h3>Italy</h3>
                        <p>19+ Activities</p>
                    </div>
                </div>

                <div class="carousel-card">
                    <img src="assets/img/INDIA.jpg">
                    <div class="card-info">
                        <h3>India</h3>
                        <p>35+ Activities • 42+ Tours </p>
                    </div>
                </div>

            </div>
        </div>

        <button class="carousel-btn right" onclick="slideRight()">&#10095;</button>
    </div>
</section>


<!-- ================= OFFER ================= -->
<section class="offer-banner">
    <div class="offer-content">
        <span class="offer-badge">🔥 Limited Time Offer</span>
        <h2>Up to <span>30% OFF</span> on International Trips</h2>
        <p>Book your dream vacation today.</p>
        <a href="destinations.php" class="offer-btn">View Offers</a>
    </div>
</section>

<!-- ================= CTA ================= -->
<section class="cta">
    <h2>Ready to Start Your Journey?</h2>
    <p>Book your next travel destination with Mr.Traveller!</p>
    <a href="destinations.php" class="cta-btn">Book Now</a>
</section>

<footer class="footer">
    <p>&copy; 2025 Mr.Traveller. All Rights Reserved.</p>
</footer>

<script>
const track = document.getElementById("carouselTrack");
const cardWidth = 300; // card width + gap
let position = 0;

function slideRight() {
    const maxScroll = -(track.scrollWidth - document.querySelector(".carousel-window").offsetWidth);
    position -= cardWidth;
    if (position < maxScroll) position = maxScroll;
    track.style.transform = `translateX(${position}px)`;
}

function slideLeft() {
    position += cardWidth;
    if (position > 0) position = 0;
    track.style.transform = `translateX(${position}px)`;
}
</script>



</body>
</html>
