<?php
session_start();
include 'db_connection.php'; // Ensure this file includes your correct DB connection details

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Check if boat ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "No boat ID specified.";
    exit();
}

// Get the boat ID from the URL
$boat_id = $_GET['id'];

// Fetch the boat details from the database to get the image path
$stmt = $conn->prepare("SELECT image_path FROM boats WHERE id = ?");
$stmt->bind_param("i", $boat_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Boat not found.";
    exit();
}

$boat = $result->fetch_assoc();
$image_path = $boat['image_path'];

// Delete the boat from the database
$stmt = $conn->prepare("DELETE FROM boats WHERE id = ?");
$stmt->bind_param("i", $boat_id);

if ($stmt->execute()) {
    // Optionally, delete the image file from the server if it exists
    if (!empty($image_path) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $image_path); // Deletes the image file
    }

    // Redirect to the admin dashboard or another page after deletion
    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "Error deleting boat. Please try again.";
}

$stmt->close();
$conn->close();
?>
