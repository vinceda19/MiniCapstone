    <?php
    session_start();
    include 'db_connection.php'; // Include the database connection

    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'boat_owner') {
        header("Location: login.php");
        exit();
    }

    // Fetch boat owner details
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM boat_owner_details WHERE user_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $boat_owner = $result->fetch_assoc();
    $stmt->close();

    // Fetch boats added by the boat owner
    $stmt = $conn->prepare("SELECT * FROM boats WHERE owner_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $boats = $stmt->get_result();
    $stmt->close();

    // Fetch bookings for boats owned by the boat owner
    $stmt = $conn->prepare("SELECT b.id AS booking_id, b.booking_date, b.duration, b.status, c.first_name, c.last_name, bo.boat_name 
                            FROM bookings b 
                            JOIN boats bo ON b.boat_id = bo.id 
                            JOIN customer_details c ON b.customer_id = c.user_id 
                            WHERE bo.owner_id = ?");
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
        <title>Boat Owner Dashboard</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 font-sans">

        <!-- Main Container -->
        <div class="max-w-6xl mx-auto my-8 p-6 bg-white rounded-lg shadow-lg">

            <!-- Dashboard Header -->
            <h1 class="text-3xl font-semibold text-center mb-6">Welcome, <?php echo htmlspecialchars($boat_owner['business_name']); ?>!</h1>

            <!-- Boats Section -->
            <section>
                <h2 class="text-2xl font-medium mb-4">Your Boats</h2>
                <?php if ($boats->num_rows > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($boat = $boats->fetch_assoc()): ?>
                            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                                <div class="relative">
                                    <!-- Boat Image -->
                                    <?php 
                                    // Check if image_path is available and file exists
                                    if (!empty($boat['image_path']) && file_exists($boat['image_path'])): 
                                    ?>
                                        <!-- Show the image -->
                                        <img src="<?php echo htmlspecialchars($boat['image_path']); ?>" alt="<?php echo htmlspecialchars($boat['boat_name']); ?>" class="w-full h-56 object-cover">
                                    <?php else: ?>
                                        <!-- Fallback message -->
                                        <div class="w-full h-56 bg-gray-300 flex items-center justify-center text-gray-600">
                                            No Image Available
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($boat['boat_name']); ?></h3>
                                    <p class="text-gray-600 mt-2"><strong>Capacity:</strong> <?php echo htmlspecialchars($boat['capacity']); ?> people</p>
                                    <p class="text-gray-700 mt-2"><?php echo nl2br(htmlspecialchars($boat['description'])); ?></p>
                                </div>
                                <div class="px-4 py-2 bg-gray-100 text-center">
                                    <a href="edit_boat.php?id=<?php echo $boat['id']; ?>" class="text-blue-500 hover:text-blue-700">Edit</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-700">You have not added any boats yet.</p>
                <?php endif; ?>
            </section>

            <!-- Bookings Section -->
            <section class="mt-8">
                <h2 class="text-2xl font-medium mb-4">Bookings for Your Boats</h2>
                <?php if ($bookings->num_rows > 0): ?>
                    <table class="min-w-full table-auto border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-gray-700">
                                <th class="px-4 py-2 border">Booking ID</th>
                                <th class="px-4 py-2 border">Boat Name</th>
                                <th class="px-4 py-2 border">Booking Date</th>
                                <th class="px-4 py-2 border">Duration</th>
                                <th class="px-4 py-2 border">Status</th>
                                <th class="px-4 py-2 border">Customer Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <tr class="border-b">
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($booking['boat_name']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($booking['duration']); ?> days</td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($booking['status']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-gray-700">No bookings have been made for your boats yet.</p>
                <?php endif; ?>
            </section>

            <!-- Action Links -->
            <div class="mt-8 text-center">
                <a href="add_boat.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Add a New Boat</a>
                <br><br>
                <a href="logout.php" class="text-red-600 hover:underline">Logout</a>
            </div>
        </div>

    </body>
    </html>
