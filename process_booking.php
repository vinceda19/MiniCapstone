<?php
session_start();
include 'db_connection.php'; // Include the database connection

// Check if the user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

$boat_id = $_POST['boat_id'];
$user_id = $_POST['user_id'];
$booking_date = $_POST['booking_date'];
$duration = $_POST['duration'];  // Duration of the booking (in hours)
$status = 'pending';  // Default booking status
$island = $_POST['island'];  // Get the selected island

// Insert the booking information into the bookings table
$stmt = $conn->prepare("INSERT INTO bookings (customer_id, boat_id, booking_date, duration, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iisis", $user_id, $boat_id, $booking_date, $duration, $status);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id; // Get the last inserted booking ID

    // Insert the island information into the booking_details table
    $stmt_details = $conn->prepare("INSERT INTO booking_details (booking_id, island) VALUES (?, ?)");
    $stmt_details->bind_param("is", $booking_id, $island);

    if ($stmt_details->execute()) {
        // Optionally, update the boat's availability
        $stmt_update = $conn->prepare("UPDATE boats SET available = 0 WHERE id = ?");
        $stmt_update->bind_param("i", $boat_id);
        $stmt_update->execute();

        echo "Booking confirmed for " . htmlspecialchars($boat['boat_name']) . " on $booking_date for $duration hours to visit $island.";
    } else {
        echo "Error saving the island information.";
    }

    $stmt_details->close();
} else {
    echo "Error processing the booking. Please try again.";
}

$stmt->close();
$conn->close();
?>
