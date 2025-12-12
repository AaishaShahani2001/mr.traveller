<?php
session_start();
require "config.php";

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Destination id required
if (!isset($_GET['id'])) {
    die("No destination selected!");
}

$dest_id = $_GET['id'];

// Fetch destination details
$sql = $conn->prepare("SELECT * FROM destinations WHERE dest_id = ?");
$sql->execute([$dest_id]);
$dest = $sql->fetch(PDO::FETCH_ASSOC);

if (!$dest) {
    die("Destination not found!");
}

// Handle form submit
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $travel_date = $_POST['travel_date'];
    $number_of_people = (int) $_POST['number_of_people'];

    if ($number_of_people < 1) {
        $error_msg = "Number of people must be at least 1.";
    } else {
        $user_id = $_SESSION['user_id'];
        $booking_date = date('Y-m-d');
        $total_amount = $dest['price'] * $number_of_people;

        $stmt = $conn->prepare("INSERT INTO bookings 
            (user_id, dest_id, booking_date, travel_date, number_of_people, total_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')");

        $stmt->execute([
            $user_id,
            $dest_id,
            $booking_date,
            $travel_date,
            $number_of_people,
            $total_amount
        ]);

        // Redirect or show message
        header("Location: my_bookings.php?msg=booked");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book: <?php echo $dest['title']; ?> - Mr.Traveller</title>

    <style>
        body { font-family: Arial; background:#f5f7ff; margin:0; padding:0; }
        .container { width:90%; margin:auto; padding:30px 0; }

        .booking-box {
            background:white;
            padding:20px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
            display:flex;
            gap:30px;
        }

        .image-box { flex:1; }
        .image-box img {
            width:100%;
            border-radius:10px;
            height:350px;
            object-fit:cover;
        }

        .form-box { flex:1.2; }

        h2 { margin-bottom:10px; }
        .location { font-weight:bold; margin-bottom:8px; }
        .price { color:#007bff; font-size:18px; margin-bottom:10px; }
        .duration { margin-bottom:15px; }

        label { display:block; margin-top:10px; font-weight:bold; }
        input, button {
            width:100%;
            padding:10px;
            margin-top:5px;
        }
        button {
            background:#007bff;
            color:white;
            border:none;
            border-radius:6px;
            font-weight:bold;
            cursor:pointer;
            margin-top:15px;
        }
        button:hover { background:#005fcc; }

        .error { color:red; margin-top:10px; }
        .success { color:green; margin-top:10px; }

    </style>
</head>
<body>

<div class="container">
    <div class="booking-box">

        <!-- LEFT: IMAGE -->
        <div class="image-box">
            <img src="uploads/<?php echo $dest['image']; ?>" alt="Image">
        </div>

        <!-- RIGHT: BOOKING FORM -->
        <div class="form-box">
            <h2><?php echo $dest['title']; ?></h2>

            <p class="location">
                <?php echo $dest['country']; ?> — <?php echo $dest['city']; ?>
            </p>

            <p class="price">Price per person: $<?php echo $dest['price']; ?></p>
            <p class="duration">Duration: <?php echo $dest['duration']; ?></p>

            <form method="POST">
                <label>Travel Date:</label>
                <input type="date" name="travel_date" required>

                <label>Number of People:</label>
                <input type="number" name="number_of_people" value="1" min="1" required>

                <button type="submit">Confirm Booking</button>
            </form>

            <?php
            if (!empty($error_msg)) echo "<p class='error'>$error_msg</p>";
            if (!empty($success_msg)) echo "<p class='success'>$success_msg</p>";
            ?>
        </div>

    </div>
</div>

</body>
</html>
