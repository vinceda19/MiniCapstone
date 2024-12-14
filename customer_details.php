<?php
session_start();
include 'db_connection.php'; // Include the database connection

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture customer details
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $age = $_POST['age'];
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];

    // Insert customer details into the customer_details table
    $stmt = $conn->prepare("INSERT INTO customer_details (user_id, first_name, middle_name, last_name, age, birthday, address, contact_number, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississsss", $_SESSION['user_id'], $first_name, $middle_name, $last_name, $age, $birthday, $address, $contact_number, $email);
    $stmt->execute();
    $stmt->close();

    // Update users table to indicate details have been submitted
    $stmt = $conn->prepare("UPDATE users SET details_submitted = TRUE WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Redirect to customer dashboard
    header("Location: customer_dashboard.php");
}
?>

<form method="POST">
    First Name: <input type="text" name="first_name" required><br>
    Middle Name: <input type="text" name="middle_name"><br>
    Last Name: <input type="text" name="last_name" required><br>
    Age: <input type="number" name="age" required><br>
    Birthday: <input type="date" name="birthday" required><br>
    Address: <input type="text" name="address" required><br>
    Contact Number: <input type="text" name="contact_number" required><br>
    Email: <input type="email" name="email" required><br>
    <input type="submit" value="Submit Details">
</form>