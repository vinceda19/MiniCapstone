<?php
session_start();
include 'db_connection.php'; // Include the database connection

// Fetch boat owners for the dropdown list
$boat_owners_query = "SELECT id, username FROM users WHERE user_type = 'boat_owner'";
$boat_owners_result = $conn->query($boat_owners_query);

// Check for query failure
if (!$boat_owners_result) {
    die("Query failed: " . $conn->error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the posted form data
    $boat_name = $_POST['boat_name'];
    $description = $_POST['description'];
    $capacity = $_POST['capacity'];
    $owner_id = $_POST['owner_id'];

    // Handle file upload
    if (isset($_FILES['boat_image']) && $_FILES['boat_image']['error'] == 0) {
        // Get image file details
        $image_tmp = $_FILES['boat_image']['tmp_name'];
        $image_name = $_FILES['boat_image']['name'];
        $image_size = $_FILES['boat_image']['size'];
        $image_type = $_FILES['boat_image']['type'];

        // Validate image (you can add more checks, e.g., file size, type, etc.)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($image_type, $allowed_types)) {
            $error = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($image_size > $max_size) {
            $error = "The image size must not exceed 5MB.";
        } else {
            // Define the upload directory (absolute or relative path)
            $upload_dir = __DIR__ . '/uploads/'; // This will get the absolute path to the "uploads" folder

            // Ensure the directory exists and is writable
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Create the directory if it doesn't exist
            }

            if (!is_writable($upload_dir)) {
                $error = "The uploads directory is not writable. Please check the directory permissions.";
            } else {
                // Move the file to the server directory
                $image_path = $upload_dir . basename($image_name);

                if (move_uploaded_file($image_tmp, $image_path)) {
                    // File upload was successful
                    $image_path_db = $image_path; // Store this path in the database
                } else {
                    $error = "Error uploading the image.";
                }
            }
        }
    } else {
        $image_path_db = NULL; // No image uploaded
    }

    // Validate inputs (basic validation)
    if (empty($boat_name) || empty($description) || empty($capacity) || empty($owner_id)) {
        $error = "Please fill all the fields.";
    } else {
        // Insert the boat into the database
        $insert_query = "INSERT INTO boats (boat_name, description, capacity, owner_id, image_path) 
                         VALUES (?, ?, ?, ?, ?)";

        // Prepare the statement
        if ($stmt = $conn->prepare($insert_query)) {
            // Bind parameters (add image_path parameter)
            $stmt->bind_param("ssiss", $boat_name, $description, $capacity, $owner_id, $image_path_db);

            // Execute the query
            if ($stmt->execute()) {
                $success = "Boat added successfully!";
            } else {
                $error = "Error adding the boat: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            $error = "Error preparing the query: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Boat</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <div class="max-w-2xl mx-auto mt-10 bg-white p-8 rounded shadow-lg">
        <h1 class="text-2xl font-bold text-center mb-4">Add New Boat</h1>

        <!-- Show error or success message -->
        <?php if (isset($error)): ?>
            <p class="text-red-500"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <p class="text-green-500"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST" action="add_boat.php" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="boat_name" class="block text-sm font-medium text-gray-700">Boat Name:</label>
                <input type="text" id="boat_name" name="boat_name" class="w-full p-2 border border-gray-300 rounded" required>
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Description:</label>
                <textarea id="description" name="description" class="w-full p-2 border border-gray-300 rounded" required></textarea>
            </div>

            <div class="mb-4">
                <label for="capacity" class="block text-sm font-medium text-gray-700">Capacity:</label>
                <input type="number" id="capacity" name="capacity" class="w-full p-2 border border-gray-300 rounded" required>
            </div>

            <div class="mb-4">
                <label for="owner_id" class="block text-sm font-medium text-gray-700">Boat Owner:</label>
                <select id="owner_id" name="owner_id" class="w-full p-2 border border-gray-300 rounded" required>
                    <option value="">Select a Boat Owner</option>
                    <?php while ($owner = $boat_owners_result->fetch_assoc()): ?>
                        <option value="<?php echo $owner['id']; ?>"><?php echo htmlspecialchars($owner['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="boat_image" class="block text-sm font-medium text-gray-700">Boat Image:</label>
                <input type="file" id="boat_image" name="boat_image" accept="image/*" class="w-full p-2 border border-gray-300 rounded">
            </div>

            <div class="flex justify-center">
                <a href="boat_owner_dashboard.php"><button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Add Boat</button></a>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="boat_owner_dashboard.php" class="text-blue-500 hover:underline">Back to Dashboard</a>
        </div>
    </div>

</body>
</html>

<?php
$conn->close();
?>
