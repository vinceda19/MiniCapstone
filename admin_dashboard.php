<?php
session_start();
include 'db_connection.php'; // Ensure this file includes your correct DB connection details

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch customer details directly from customer_details table
$customers_query = "SELECT c.id, c.first_name, c.middle_name, c.last_name, c.age, c.birthday, c.address, c.contact_number, c.email
                    FROM customer_details c";

// Execute the query and check for errors
$customers_result = $conn->query($customers_query);

// Check if the query executed successfully
if (!$customers_result) {
    die("Query failed: " . $conn->error);  // Print any SQL error if the query fails
}

// Fetch boat owner details along with additional information from boat_owner_details table
$boat_owners_query = "SELECT u.id, u.username, bo.business_name, bo.address AS business_address, bo.contact_number AS business_contact, bo.email AS business_email,
           b.boat_name, b.description, b.capacity
    FROM users u
    LEFT JOIN boat_owner_details bo ON u.id = bo.user_id
    LEFT JOIN boats b ON b.owner_id = u.id
    WHERE u.user_type = 'boat_owner'
";

$boat_owners_result = $conn->query($boat_owners_query);

// Check for query failure
if (!$boat_owners_result) {
    die("Query failed: " . $conn->error);
}

// Fetch all bookings including the selected island
$bookings_query = "SELECT b.id, b.booking_date, b.duration, b.status, c.username AS customer_name, bo.boat_name, bd.island 
                   FROM bookings b
                   JOIN users c ON b.customer_id = c.id
                   JOIN boats bo ON b.boat_id = bo.id
                   JOIN booking_details bd ON b.id = bd.booking_id";  // Join booking_details to get the island
$bookings_result = $conn->query($bookings_query);

// Check for query failure
if (!$bookings_result) {
    die("Query failed: " . $conn->error);
}

// Fetch boat details
$boats_query = "SELECT b.id, b.boat_name, b.description, b.capacity, b.owner_id, bo.business_name, b.image_path
                FROM boats b
                LEFT JOIN boat_owner_details bo ON b.owner_id = bo.user_id";
$boats_result = $conn->query($boats_query);

if (!$boats_result) {
    die("Query failed: " . $conn->error);
}

// Update booking status if the form is submitted
if (isset($_POST['update_booking_status'])) {
    // Loop through each booking and update the status
    foreach ($_POST['status'] as $booking_id => $status) {
        $update_query = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $status, $booking_id);

        if (!$stmt->execute()) {
            die("Error updating booking status: " . $stmt->error);
        }
    }
    // Redirect to avoid resubmitting the form on refresh
    header("Location: admin_dashboard.php");
    exit();
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.1.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold text-center mb-6">Admin Dashboard</h1>

        <!-- Customers Section -->
        <h2 class="text-2xl font-medium mb-4">Customers</h2>
        <?php if ($customers_result->num_rows > 0): ?>
            <table class="min-w-full table-auto bg-white shadow-md rounded-lg">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b">ID</th>
                        <th class="px-4 py-2 border-b">Name</th>
                        <th class="px-4 py-2 border-b">Age</th>
                        <th class="px-4 py-2 border-b">Birthday</th>
                        <th class="px-4 py-2 border-b">Address</th>
                        <th class="px-4 py-2 border-b">Contact Number</th>
                        <th class="px-4 py-2 border-b">Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($customer = $customers_result->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['id']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['first_name'] . " " . $customer['middle_name'] . " " . $customer['last_name']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['age']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['birthday']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['address']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['contact_number']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($customer['email']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-700">No customers found.</p>
        <?php endif; ?>

        <!-- Boat Owners Section -->
        <h2 class="text-2xl font-medium mb-4">Boat Owners</h2>
        <?php if ($boat_owners_result->num_rows > 0): ?>
            <table class="min-w-full table-auto bg-white shadow-md rounded-lg">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b">ID</th>
                        <th class="px-4 py-2 border-b">Username</th>
                        <th class="px-4 py-2 border-b">Email</th>
                        <th class="px-4 py-2 border-b">Business Name</th>
                        <th class="px-4 py-2 border-b">Business Address</th>
                        <th class="px-4 py-2 border-b">Business Contact</th>
                        <th class="px-4 py-2 border-b">Boat Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($boat_owner = $boat_owners_result->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['id']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['username']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['business_email']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['business_name']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['business_address']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['business_contact']); ?></td>
                            <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($boat_owner['boat_name']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-700">No boat owners found.</p>
        <?php endif; ?>

        <!-- Boats Section -->
        <h2 class="text-2xl font-medium mb-4">Boats</h2>
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
                            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($boat['boat_name']); ?></h3>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($boat['description']); ?></p>
                            <div class="mt-4">
                                <p class="text-gray-700"><strong>Capacity:</strong> <?php echo htmlspecialchars($boat['capacity']); ?></p>
                                <p class="text-gray-700"><strong>Business Name:</strong> <?php echo htmlspecialchars($boat['business_name']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-700">No boats found.</p>
            <?php endif; ?>
        </div>

        <!-- Bookings Section -->
<h2 class="text-2xl font-medium mb-4">Bookings</h2>
<?php if ($bookings_result->num_rows > 0): ?>
    <form action="admin_dashboard.php" method="POST">
        <table class="min-w-full table-auto bg-white shadow-md rounded-lg">
            <thead>
                <tr>
                    <th class="px-4 py-2 border-b">ID</th>
                    <th class="px-4 py-2 border-b">Customer</th>
                    <th class="px-4 py-2 border-b">Boat</th>
                    <th class="px-4 py-2 border-b">Island</th>
                    <th class="px-4 py-2 border-b">Booking Date</th>
                    <th class="px-4 py-2 border-b">Duration</th>
                    <th class="px-4 py-2 border-b">Status</th>
                    <th class="px-4 py-2 border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                    <tr>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['boat_name']); ?></td>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['island']); ?></td>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['duration']); ?> days</td>
                        <td class="px-4 py-2 border-b"><?php echo htmlspecialchars($booking['status']); ?></td>
                        <td class="px-4 py-2 border-b">
                            <select name="status[<?php echo $booking['id']; ?>]" class="px-4 py-2 rounded-lg border border-gray-300">
                                <option value="Pending" <?php echo ($booking['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo ($booking['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Cancelled" <?php echo ($booking['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="text-right mt-4">
            <button type="submit" name="update_booking_status" class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-blue-600">Update Status</button>
        </div>
    </form>
<?php else: ?>
    <p class="text-gray-700">No bookings found.</p>
<?php endif; ?>


    <div class="text-right mb-4">
        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-red-600">Logout</a>
    </div>

</body>
</html>

<?php
$conn->close();
?>
