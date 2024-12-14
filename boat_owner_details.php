<?php
session_start();
include 'db_connection.php'; // Include the database connection

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'boat_owner') {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $business_name = $_POST['business_name'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO boat_owner_details (user_id, business_name, address, contact_number, email) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . htmlspecialchars($conn->error));
    }

    // Bind parameters
    $stmt->bind_param("issss", $_SESSION['user_id'], $business_name, $address, $contact_number, $email);

    // Execute the statement
    if ($stmt->execute()) {
        // Update the users table to mark details as submitted
        $update_stmt = $conn->prepare("UPDATE users SET details_submitted = TRUE WHERE id = ?");
        if ($update_stmt === false) {
            die("Error preparing update statement: " . htmlspecialchars($conn->error));
        }
        $update_stmt->bind_param("i", $_SESSION['user_id']);
        $update_stmt->execute();
        $update_stmt->close();

        // Redirect to boat owner dashboard
        header("Location: boat_owner_dashboard.php");
        exit();
    } else {
        echo "Error executing statement: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>

<form method="POST">
    <h1>Boat Owner Details</h1>
    <label for="business_name">Business Name:</label>
    <input type="text" name="business_name" id="business_name" required><br>

    <label for="address">Address:</label>
    <input type="text" name="address" id="address" required><br>

    <label for="contact_number">Contact Number:</label>
    <input type="text" name="contact_number" id="contact_number" required><br>

    <label for="email">Email:</label>
    <input type="email" name="email" id="email" required><br>

    <input type="submit" value="Submit Details">
</form>
