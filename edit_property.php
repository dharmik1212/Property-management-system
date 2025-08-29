<?php
session_start();
require_once 'db.php';
check_login();

if (!isset($_GET['id'])) {
    header("Location: dashboard_client.php");
    exit();
}

$property_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch property details
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND (user_id = ? OR ? = (SELECT id FROM users WHERE role = 'admin' AND id = ?))");
$stmt->bind_param("iiii", $property_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Property not found or access denied";
    header("Location: dashboard_client.php");
    exit();
}

$property = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $location = sanitize_input($_POST['location']);
    $price = floatval($_POST['price']);
    $property_type = sanitize_input($_POST['property_type']);
    $status = sanitize_input($_POST['status']);

    // Validate price range
    if ($price > 99999999) {
        $_SESSION['error'] = "Price cannot exceed $99,999,999. Please enter a realistic price.";
    } elseif (empty($title) || empty($description) || empty($location) || $price <= 0) {
        $_SESSION['error'] = "All fields are required and price must be greater than 0";
    } else {
        $update_stmt = $conn->prepare("UPDATE properties SET title = ?, description = ?, location = ?, price = ?, property_type = ?, status = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("sssdssii", $title, $description, $location, $price, $property_type, $status, $property_id, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Property updated successfully";
            header("Location: dashboard_client.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating property: " . $conn->error;
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Property Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_client.php">Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="property-form">
            <h2 class="text-center mb-4">Edit Property</h2>
            <?php
            if (isset($_SESSION['error'])) {
                echo show_error($_SESSION['error']);
                unset($_SESSION['error']);
            }
            ?>
            <form action="edit_property.php?id=<?php echo $property_id; ?>" method="post">
                <div class="mb-3">
                    <label for="title" class="form-label">Property Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($property['location']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" max="99999999" value="<?php echo htmlspecialchars($property['price']); ?>" required>
                    </div>
                    <small class="form-text text-muted">Maximum price: $99,999,999</small>
                </div>
                <div class="mb-3">
                    <label for="property_type" class="form-label">Property Type</label>
                    <select class="form-select" id="property_type" name="property_type" required>
                        <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>House</option>
                        <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                        <option value="condo" <?php echo $property['property_type'] === 'condo' ? 'selected' : ''; ?>>Condo</option>
                        <option value="land" <?php echo $property['property_type'] === 'land' ? 'selected' : ''; ?>>Land</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo $property['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="rented" <?php echo $property['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update Property</button>
                    <a href="dashboard_client.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
