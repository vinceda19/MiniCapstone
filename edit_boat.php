<?php
session_start();
include 'db_connection.php'; // Include the database connection

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'boat_owner') {
    header("Location: login.php");
    exit();
}

// Check if boat ID is passed as a parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: boat_owner_dashboard.php");
    exit();
}

$boat_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch boat details for the given ID
$stmt = $conn->prepare("SELECT * FROM boats WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $boat_id, $user_id);
$stmt->execute();
$boat_result = $stmt->get_result();

if ($boat_result->num_rows === 0) {
    echo "Boat not found or you do not have permission to edit this boat.";
    exit();
}

$boat = $boat_result->fetch_assoc();
$stmt->close();

// Handle the form submission for editing boat details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $boat_name = $_POST['boat_name'];
    $capacity = $_POST['capacity'];
    $description = $_POST['description'];
    $image_path = $boat['image_path']; // Keep existing image unless a new one is uploaded

    // Handle the image upload
    if (isset($_FILES['boat_image']) && $_FILES['boat_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES['boat_image']['name']);
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is a valid image
        if (in_array($image_file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['boat_image']['tmp_name'], $target_file)) {
                $image_path = $target_file; // Update image path if upload is successful
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "Only image files are allowed (JPG, JPEG, PNG, GIF).";
        }
    }

    // Update boat details in the database
    $stmt = $conn->prepare("UPDATE boats SET boat_name = ?, capacity = ?, description = ?, image_path = ? WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("sissii", $boat_name, $capacity, $description, $image_path, $boat_id, $user_id);

    if ($stmt->execute()) {
        header("Location: boat_owner_dashboard.php"); // Redirect to dashboard after successful update
        exit();
    } else {
        echo "Error updating boat details. Please try again.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Boat</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto my-8 p-6 bg-white rounded-lg shadow-lg">

        <h1 class="text-3xl font-semibold text-center mb-6">Edit Boat: <?php echo htmlspecialchars($boat['boat_name']); ?></h1>

        <form action="edit_boat.php?id=<?php echo $boat['id']; ?>" method="POST" enctype="multipart/form-data">
            <!-- Boat Name -->
            <div class="mb-4">
                <label for="boat_name" class="block text-gray-700 font-medium">Boat Name</label>
                <input type="text" id="boat_name" name="boat_name" value="<?php echo htmlspecialchars($boat['boat_name']); ?>" class="w-full p-2 border border-gray-300 rounded-lg" required>
            </div>

            <!-- Capacity -->
            <div class="mb-4">
                <label for="capacity" class="block text-gray-700 font-medium">Capacity</label>
                <input type="number" id="capacity" name="capacity" value="<?php echo htmlspecialchars($boat['capacity']); ?>" class="w-full p-2 border border-gray-300 rounded-lg" required>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="block text-gray-700 font-medium">Description</label>
                <textarea id="description" name="description" rows="4" class="w-full p-2 border border-gray-300 rounded-lg" required><?php echo htmlspecialchars($boat['description']); ?></textarea>
            </div>

            <!-- Boat Image -->
            <div class="mb-4">
                <label for="boat_image" class="block text-gray-700 font-medium">Upload New Image (Optional)</label>
                <input type="file" id="boat_image" name="boat_image" accept="image/*" class="w-full p-2 border border-gray-300 rounded-lg">
                <p class="text-gray-500 mt-2">Current Image: 
                    <?php if (!empty($boat['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($boat['image_path']); ?>" alt="Boat Image" class="w-24 mt-2 rounded-lg">
                    <?php else: ?>
                        No image available.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center mt-6">
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Boat</button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="boat_owner_dashboard.php" class="text-gray-600 hover:underline">Back to Dashboard</a>
        </div>
    </div>

</body>
</html>
