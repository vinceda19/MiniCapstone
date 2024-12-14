<?php
session_start();
include 'db_connection.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];

    // Validate inputs
    if (empty($username) || empty($password) || empty($user_type)) {
        echo "All fields are required.";
    } elseif ($password !== $confirm_password) {
        echo "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo "Username is already taken.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the database
            $stmt = $conn->prepare("INSERT INTO users (username, password, user_type, details_submitted) VALUES (?, ?, ?, FALSE)");
            $stmt->bind_param("sss", $username, $hashed_password, $user_type);

            if ($stmt->execute()) {
                echo "Registration successful. You can now <a href='login.php'>log in</a>.";
            } else {
                echo "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<form method="POST">
    <h1>Register</h1>
    <label for="username">Username:</label>
    <input type="text" name="username" id="username" required><br>

    <label for="password">Password:</label>
    <input type="password" name="password" id="password" required><br>

    <label for="confirm_password">Confirm Password:</label>
    <input type="password" name="confirm_password" id="confirm_password" required><br>

    <label for="user_type">User Type:</label>
    <select name="user_type" id="user_type" required>
        <option value="customer">Customer</option>
        <option value="boat_owner">Boat Owner</option>
        <option value="admin">Admin</option>
    </select><br>

    <input type="submit" value="Register">
</form>
