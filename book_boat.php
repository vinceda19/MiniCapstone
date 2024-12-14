<?php
session_start();
include 'db_connection.php'; // Include the database connection

// Check if the user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

// Check if boat_id is specified
if (!isset($_GET['boat_id']) || empty($_GET['boat_id'])) {
    die("No Boat ID specified.");
}

$boat_id = $_GET['boat_id'];

// Fetch boat details from the database
$stmt = $conn->prepare("SELECT b.id, b.boat_name, b.capacity, b.owner_id, bo.business_name
                        FROM boats b
                        LEFT JOIN boat_owner_details bo ON b.owner_id = bo.user_id
                        WHERE b.id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $boat_id);
$stmt->execute();
$boat_result = $stmt->get_result();

if ($boat_result->num_rows == 0) {
    die("Boat not found.");
}

$boat = $boat_result->fetch_assoc();
$stmt->close();

// Fetch available islands from your database (assuming there's an 'islands' table)
$islands_query = "SELECT island_name FROM islands"; // Assuming an 'islands' table with a column 'island_name'
$islands_result = $conn->query($islands_query);
$islands = [];

if ($islands_result->num_rows > 0) {
    while ($row = $islands_result->fetch_assoc()) {
        $islands[] = $row['island_name'];
}

}

// Fetch customer details from session
$user_id = $_SESSION['user_id'];

// Handle the form submission for booking
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the booking details from the form
    $booking_date = $_POST['booking_date'];
    $duration = $_POST['duration']; // Duration in hours
    $island = $_POST['island']; // Island selected by the customer

    // Check if there's already a booking for the boat on the selected date
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE boat_id = ? AND booking_date = ?");
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("is", $boat_id, $booking_date);
    $stmt->execute();
    $existing_booking = $stmt->get_result();

    if ($existing_booking->num_rows > 0) {
        // If booking already exists, show an error message
        $error_message = "This boat is already booked for the selected date. Please choose another date.";
    } else {
        // Insert booking into the database if no existing booking is found
        $stmt = $conn->prepare("INSERT INTO bookings (customer_id, boat_id, booking_date, duration, status) 
                                VALUES (?, ?, ?, ?, 'Pending')");
        if ($stmt === false) {
            die("Prepare failed: " . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("iisd", $user_id, $boat_id, $booking_date, $duration);
        $stmt->execute();
        $booking_id = $stmt->insert_id; // Get the last inserted booking ID
        $stmt->close();

        // Now insert into the booking_details table for the island selection
        $stmt = $conn->prepare("INSERT INTO booking_details (booking_id, island) VALUES (?, ?)");
        if ($stmt === false) {
            die("Prepare failed: " . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("is", $booking_id, $island);
        $stmt->execute();
        $stmt->close();

        // Redirect to the dashboard or booking confirmation page
        header("Location: customer_dashboard.php");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Boat</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold text-center text-gray-800 mb-6">Book <?php echo htmlspecialchars($boat['boat_name']); ?></h1>

        <!-- Boat Details -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h2 class="text-2xl font-medium text-gray-700 mb-4"><?php echo htmlspecialchars($boat['boat_name']); ?></h2>
            <p><strong>Business Name:</strong> <?php echo htmlspecialchars($boat['business_name']); ?></p>
            <p><strong>Capacity:</strong> <?php echo htmlspecialchars($boat['capacity']); ?> people</p>
        </div>

        <!-- Display Error Message if Booking Exists -->
        <?php if (isset($error_message)): ?>
            <div class="bg-red-200 p-4 rounded-lg mb-4">
                <p class="text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Booking Form -->
        <form action="book_boat.php?boat_id=<?php echo htmlspecialchars($boat['id']); ?>" method="POST" class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-xl font-medium text-gray-700 mb-4">Booking Details</h3>
            
            <div class="mb-4">
                <label for="booking_date" class="block text-gray-700">Booking Date</label>
                <input type="date" name="booking_date" id="booking_date" required class="w-full p-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label for="duration" class="block text-gray-700">Duration (in hours)</label>
                <input type="number" name="duration" id="duration" min="1" required class="w-full p-2 border border-gray-300 rounded-md">
            </div>

            <div class="mb-4">
                <label for="island" class="block text-gray-700">Island Destination</label>
                <select name="island" id="island" required class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Select Island</option>
                    <?php foreach ($islands as $island): ?>
                        <option value="<?php echo htmlspecialchars($island); ?>"><?php echo htmlspecialchars($island); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-600">Confirm Booking</button>
            </div>
        </form>

        <!-- Cancel Link -->
        <div class="mt-6 text-center">
            <a href="customer_dashboard.php" class="text-blue-500 hover:text-blue-700">Cancel</a>
        </div>
    </div>

</body>
</html>
