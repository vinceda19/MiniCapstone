<?php
session_start();
include 'db_connection.php'; // Include the database connection

// Check if the user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'customer') {
    header("Location: login.php");
    exit();
}

// Fetch customer details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM customer_details WHERE user_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

// Fetch boat details for customers to view and book
$boats_query = "SELECT b.id, b.boat_name, b.description, b.capacity, b.owner_id, bo.business_name, b.image_path
                FROM boats b
                LEFT JOIN boat_owner_details bo ON b.owner_id = bo.user_id";
$boats_result = $conn->query($boats_query);

if (!$boats_result) {
    die("Query failed: " . $conn->error);
}

// Fetch bookings for the customer (including the island they selected)
$stmt = $conn->prepare("SELECT b.id, b.booking_date, b.duration, b.status, boat.boat_name, bd.island 
                         FROM bookings b 
                         JOIN boats boat ON b.boat_id = boat.id 
                         LEFT JOIN booking_details bd ON b.id = bd.booking_id
                         WHERE b.customer_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <div class="container mx-auto p-6">
        <!-- Welcome Section -->
        <h1 class="text-3xl font-semibold text-center text-gray-800 mb-6">Welcome, <?php echo htmlspecialchars($customer['first_name']); ?>!</h1>

        <!-- Customer Details Section -->
        <div class="bg-white p-6 rounded-lg shadow-lg mb-6">
            <h2 class="text-2xl font-medium text-gray-700 mb-4">Your Details</h2>
            <p><strong>First Name:</strong> <?php echo htmlspecialchars($customer['first_name']); ?></p>
            <p><strong>Middle Name:</strong> <?php echo htmlspecialchars($customer['middle_name']); ?></p>
            <p><strong>Last Name:</strong> <?php echo htmlspecialchars($customer['last_name']); ?></p>
            <p><strong>Age:</strong> <?php echo htmlspecialchars($customer['age']); ?></p>
            <p><strong>Birthday:</strong> <?php echo htmlspecialchars($customer['birthday']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($customer['address']); ?></p>
            <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($customer['contact_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
        </div>

        <!-- Available Boats Section -->
        <h2 class="text-2xl font-medium text-gray-700 mb-4">Available Boats</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if ($boats_result->num_rows > 0): ?>
                <?php while ($boat = $boats_result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <!-- Boat Image -->
                        <div class="w-full h-56 bg-cover bg-center" style="background-image: url('<?php echo htmlspecialchars($boat['image_path']); ?>');">
                            <?php if (empty($boat['image_path']) || !file_exists($boat['image_path'])): ?>
                                <div class="flex items-center justify-center w-full h-full bg-gray-200">
                                    <span class="text-white">No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Boat Details -->
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($boat['boat_name']); ?></h3>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($boat['description']); ?></p>
                            <div class="mt-4">
                                <p class="text-gray-700"><strong>Capacity:</strong> <?php echo htmlspecialchars($boat['capacity']); ?></p>
                                <p class="text-gray-700"><strong>Business Name:</strong> <?php echo htmlspecialchars($boat['business_name']); ?></p>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="book_boat.php?boat_id=<?php echo htmlspecialchars($boat['id']); ?>" 
                                   class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-600">
                                   Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-700">No boats available.</p>
            <?php endif; ?>
        </div>

        <!-- Bookings Section -->
        <h2 class="text-2xl font-medium text-gray-700 mt-12 mb-4">Your Bookings</h2>
        <?php if ($bookings->num_rows > 0): ?>
            <table class="min-w-full bg-white shadow-lg rounded-lg">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="py-3 px-6 text-left">Booking ID</th>
                        <th class="py-3 px-6 text-left">Boat Name</th>
                        <th class="py-3 px-6 text-left">Booking Date</th>
                        <th class="py-3 px-6 text-left">Duration (hours)</th>
                        <th class="py-3 px-6 text-left">Island</th>
                        <th class="py-3 px-6 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['id']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['boat_name']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['duration']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['island']); ?></td>
                            <td class="py-3 px-6"><?php echo htmlspecialchars($booking['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-700">You have no bookings yet.</p>
        <?php endif; ?>

        <!-- Logout Link -->
        <div class="mt-6 text-center">
            <a href="logout.php" class="text-blue-500 hover:text-blue-700">Logout</a>
        </div>
    </div>

</body>
</html>
