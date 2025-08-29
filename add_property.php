<?php
session_start();
require_once 'db.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $price = floatval($_POST['price']);
    $location = sanitize_input($_POST['location']);
    $property_type = sanitize_input($_POST['property_type']);
    $status = sanitize_input($_POST['status']);
    $user_id = $_SESSION['user_id'];

    // Validation
    $errors = [];
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($price <= 0) $errors[] = "Price must be greater than 0";
    if ($price > 99999999) $errors[] = "Price cannot exceed $99,999,999. Please enter a realistic price.";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($property_type)) $errors[] = "Property type is required";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO properties (title, description, price, location, property_type, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssi", $title, $description, $price, $location, $property_type, $status, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Property added successfully!";
            header("Location: " . ($_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_client.php'));
            exit();
        } else {
            $_SESSION['error'] = "Error adding property. Please try again.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-building me-2"></i>Property Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_client.php'; ?>">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="add_property.php">
                                <i class="fas fa-plus-circle me-2"></i>Add Property
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add New Property</h1>
                </div>

                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['error'] . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
                    unset($_SESSION['error']);
                }
                ?>

                <div class="card">
                    <div class="card-body">
                        <form action="add_property.php" method="post" class="needs-validation" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading me-2"></i>Title
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" required
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                                    <div class="invalid-feedback">
                                        Please provide a title.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="property_type" class="form-label">
                                        <i class="fas fa-home me-2"></i>Property Type
                                    </label>
                                    <select class="form-select" id="property_type" name="property_type" required>
                                        <option value="">Select type...</option>
                                        <option value="house" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] === 'house') ? 'selected' : ''; ?>>House</option>
                                        <option value="apartment" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] === 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                                        <option value="condo" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] === 'condo') ? 'selected' : ''; ?>>Condo</option>
                                        <option value="land" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] === 'land') ? 'selected' : ''; ?>>Land</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a property type.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="price" class="form-label">
                                        <i class="fas fa-tag me-2"></i>Price
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="price" name="price" required
                                               min="0" max="99999999" step="0.01"
                                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                                    </div>
                                    <small class="form-text text-muted">Maximum price: $99,999,999</small>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please provide a valid price.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-info-circle me-2"></i>Status
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="available" <?php echo (isset($_POST['status']) && $_POST['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="sold" <?php echo (isset($_POST['status']) && $_POST['status'] === 'sold') ? 'selected' : ''; ?>>Sold</option>
                                        <option value="rented" <?php echo (isset($_POST['status']) && $_POST['status'] === 'rented') ? 'selected' : ''; ?>>Rented</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a status.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="location" class="form-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Location
                                    </label>
                                    <input type="text" class="form-control" id="location" name="location" required
                                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                                    <div class="invalid-feedback">
                                        Please provide a location.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                    <div class="invalid-feedback">
                                        Please provide a description.
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="<?php echo $_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_client.php'; ?>" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Property
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Add loading spinner
        document.querySelector('form').addEventListener('submit', function() {
            const spinner = document.createElement('div');
            spinner.className = 'spinner-overlay';
            spinner.innerHTML = '<div class="spinner-border text-light" role="status"></div>';
            document.body.appendChild(spinner);
        });
    </script>
</body>
</html>
