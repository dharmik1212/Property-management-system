<?php
session_start();
require_once 'db.php';
check_login();

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : '';
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$where_conditions = [];
$params = [];
$types = '';

if (!$is_admin) {
    $where_conditions[] = "p.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($type_filter)) {
    $where_conditions[] = "p.property_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($price_min)) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $price_min;
    $types .= 'd';
}

if (!empty($price_max)) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $price_max;
    $types .= 'd';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(p.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(p.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "SELECT p.*, u.username as owner_name, u.email as owner_email 
          FROM properties p 
          LEFT JOIN users u ON p.user_id = u.id 
          $where_clause 
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_properties = $result->num_rows;
$total_value = 0;
$properties_data = [];

while ($row = $result->fetch_assoc()) {
    $total_value += $row['price'];
    $properties_data[] = $row;
}

$average_price = $total_properties > 0 ? $total_value / $total_properties : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Inventory Report - Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 0.25em 0.6em;
        }
        .property-card {
            transition: transform 0.2s;
        }
        .property-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
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
                            <a class="nav-link" href="<?php echo $is_admin ? 'dashboard_admin.php' : 'dashboard_client.php'; ?>">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_property.php">
                                <i class="fas fa-plus-circle me-2"></i>Add Property
                            </a>
                        </li>
                        <?php if ($is_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="property_report.php">
                                <i class="fas fa-home me-2"></i>Property Report
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Property Inventory Report</h1>
                    <div class="btn-group">
                        <a href="export_properties.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success" target="_blank">
                            <i class="fas fa-file-excel me-2"></i>Export to Excel
                        </a>
                        <a href="export_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-danger" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i>Export to PDF
                        </a>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary property-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Properties</h5>
                                        <h2 class="mb-0"><?php echo $total_properties; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success property-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Value</h5>
                                        <h2 class="mb-0">$<?php echo number_format($total_value, 2); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info property-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Average Price</h5>
                                        <h2 class="mb-0">$<?php echo number_format($average_price, 2); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning property-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Report Date</h5>
                                        <h6 class="mb-0"><?php echo date('M d, Y'); ?></h6>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section">
                    <h5><i class="fas fa-filter me-2"></i>Filters</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">All Statuses</option>
                                <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                <option value="rented" <?php echo $status_filter === 'rented' ? 'selected' : ''; ?>>Rented</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" name="type" id="type">
                                <option value="">All Types</option>
                                <option value="house" <?php echo $type_filter === 'house' ? 'selected' : ''; ?>>House</option>
                                <option value="apartment" <?php echo $type_filter === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                <option value="condo" <?php echo $type_filter === 'condo' ? 'selected' : ''; ?>>Condo</option>
                                <option value="land" <?php echo $type_filter === 'land' ? 'selected' : ''; ?>>Land</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="price_min" class="form-label">Min Price</label>
                            <input type="number" class="form-control" name="price_min" id="price_min" 
                                   value="<?php echo $price_min; ?>" placeholder="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label for="price_max" class="form-label">Max Price</label>
                            <input type="number" class="form-control" name="price_max" id="price_max" 
                                   value="<?php echo $price_max; ?>" placeholder="1000000" min="0" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" name="date_from" id="date_from" 
                                   value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" name="date_to" id="date_to" 
                                   value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>
                            <a href="property_report.php" class="btn btn-secondary">
                                <i class="fas fa-undo me-2"></i>Clear Filters
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Properties Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Property Details (<?php echo $total_properties; ?> properties found)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($total_properties > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Price</th>
                                        <th>Owner</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties_data as $property): ?>
                                    <tr>
                                        <td><strong><?php echo $property['id']; ?></strong></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($property['title']); ?></strong>
                                            <?php if (!empty($property['description'])): ?>
                                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($property['description']), 0, 50) . '...'; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($property['location']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary status-badge"><?php echo ucfirst($property['property_type']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $property['status'] === 'available' ? 'success' : ($property['status'] === 'sold' ? 'danger' : 'warning'); ?> status-badge">
                                                <?php echo ucfirst($property['status']); ?>
                                            </span>
                                        </td>
                                        <td><strong>$<?php echo number_format($property['price'], 2); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($property['owner_name']); ?>
                                            <br><small class="text-muted"><?php echo $property['owner_email']; ?></small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="edit_property.php?id=<?php echo $property['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-info" title="View Details"
                                                        data-bs-toggle="modal" data-bs-target="#propertyModal<?php echo $property['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Property Details Modal -->
                                    <div class="modal fade" id="propertyModal<?php echo $property['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Property Details - <?php echo htmlspecialchars($property['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Basic Information</h6>
                                                            <table class="table table-sm">
                                                                <tr><td><strong>ID:</strong></td><td><?php echo $property['id']; ?></td></tr>
                                                                <tr><td><strong>Title:</strong></td><td><?php echo htmlspecialchars($property['title']); ?></td></tr>
                                                                <tr><td><strong>Type:</strong></td><td><?php echo ucfirst($property['property_type']); ?></td></tr>
                                                                <tr><td><strong>Status:</strong></td><td><?php echo ucfirst($property['status']); ?></td></tr>
                                                                <tr><td><strong>Price:</strong></td><td>$<?php echo number_format($property['price'], 2); ?></td></tr>
                                                            </table>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Location & Owner</h6>
                                                            <table class="table table-sm">
                                                                <tr><td><strong>Location:</strong></td><td><?php echo htmlspecialchars($property['location']); ?></td></tr>
                                                                <tr><td><strong>Owner:</strong></td><td><?php echo htmlspecialchars($property['owner_name']); ?></td></tr>
                                                                <tr><td><strong>Email:</strong></td><td><?php echo $property['owner_email']; ?></td></tr>
                                                                <tr><td><strong>Created:</strong></td><td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td></tr>
                                                                <tr><td><strong>Updated:</strong></td><td><?php echo date('M d, Y', strtotime($property['updated_at'])); ?></td></tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-3">
                                                        <div class="col-12">
                                                            <h6>Description</h6>
                                                            <p><?php echo !empty($property['description']) ? nl2br(htmlspecialchars($property['description'])) : 'No description available.'; ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">Edit Property</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No properties found</h5>
                            <p class="text-muted">Try adjusting your filters or add new properties.</p>
                            <a href="add_property.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Property
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
