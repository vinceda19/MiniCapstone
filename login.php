<?php
session_start();
include 'db_connection.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check user credentials
    $stmt = $conn->prepare("SELECT id, password, user_type, details_submitted FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $user_type, $details_submitted);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_type'] = $user_type;

            // Redirect based on user type and details submitted
            if ($user_type == 'customer') {
                if ($details_submitted) {
                    header("Location: customer_dashboard.php");
                } else {
                    header("Location: customer_details.php");
                }
            } elseif ($user_type == 'boat_owner') {
                if ($details_submitted) {
                    header("Location: boat_owner_dashboard.php");
                } else {
                    header("Location: boat_owner_details.php");
                }
            } elseif ($user_type == 'admin') {
                header("Location: admin_dashboard.php");
            }
        } else {
            echo "Invalid credentials.";
        }
    } else {
        echo "User not found.";
    }
    $stmt->close();
    $conn->close();
}
?>

<form method="POST">
    Username: <input type="text" name="username" required>
    Password: <input type="password" name="password" required>
    <input type="submit" value="Login">
</form>
