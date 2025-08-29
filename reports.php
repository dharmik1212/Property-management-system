<?php
session_start();
require_once 'db.php';
check_login();

// Get statistics for dashboard
$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

// Total properties
$total_properties_query = $is_admin ? 
    "SELECT COUNT(*) as total FROM properties" : 
    "SELECT COUNT(*) as total FROM properties WHERE user_id = ?";
$stmt = $conn->prepare($total_properties_query);
if (!$is_admin) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$total_properties = $stmt->get_result()->fetch_assoc()['total'];

// Total value
$total_value_query = $is_admin ? 
    "SELECT SUM(price) as total FROM properties" : 
    "SELECT SUM(price) as total FROM properties WHERE user_id = ?";
$stmt = $conn->prepare($total_value_query);
if (!$is_admin) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$total_value = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Properties by status
$status_query = $is_admin ? 
    "SELECT status, COUNT(*) as count FROM properties GROUP BY status" : 
    "SELECT status, COUNT(*) as count FROM properties WHERE user_id = ? GROUP BY status";
$stmt = $conn->prepare($status_query);
if (!$is_admin) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$status_stats = $stmt->get_result();

// Properties by type
$type_query = $is_admin ? 
    "SELECT property_type, COUNT(*) as count FROM properties GROUP BY property_type" : 
    "SELECT property_type, COUNT(*) as count FROM properties WHERE user_id = ? GROUP BY property_type";
$stmt = $conn->prepare($type_query);
if (!$is_admin) {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$type_stats = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Property Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a class="nav-link active" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports & Analytics</h1>
                    <div class="btn-group">
                        <a href="export_properties.php" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Export to Excel
                        </a>
                        <a href="export_pdf.php" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>Export to PDF
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
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
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
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
                    <div class="col-md-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Average Price</h5>
                                        <h2 class="mb-0">$<?php echo $total_properties > 0 ? number_format($total_value / $total_properties, 2) : '0.00'; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Properties by Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Properties by Type
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="typeChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Reports -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Detailed Reports
                                </h5>
                            </div>
                                                         <div class="card-body">
                                 <div class="row">
                                     <div class="col-md-4">
                                         <div class="list-group">
                                             <a href="property_report.php" class="list-group-item list-group-item-action">
                                                 <i class="fas fa-home me-2"></i>Property Inventory Report
                                             </a>
                                         </div>
                                     </div>
                                     <div class="col-md-4">
                                         <div class="list-group">
                                             <a href="financial_report.php" class="list-group-item list-group-item-action">
                                                 <i class="fas fa-dollar-sign me-2"></i>Financial Summary Report
                                             </a>
                                         </div>
                                     </div>
                                     <div class="col-md-4">
                                         <div class="list-group">
                                             <a href="export_custom.php" class="list-group-item list-group-item-action">
                                                 <i class="fas fa-cog me-2"></i>Custom Export
                                             </a>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusData = {
            labels: [<?php 
                $status_labels = [];
                $status_counts = [];
                $status_colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
                $i = 0;
                while ($row = $status_stats->fetch_assoc()) {
                    $status_labels[] = "'" . ucfirst($row['status']) . "'";
                    $status_counts[] = $row['count'];
                    $i++;
                }
                echo implode(',', $status_labels);
            ?>],
            datasets: [{
                data: [<?php echo implode(',', $status_counts); ?>],
                backgroundColor: [<?php 
                    for ($i = 0; $i < count($status_counts); $i++) {
                        echo "'" . $status_colors[$i % count($status_colors)] . "'";
                        if ($i < count($status_counts) - 1) echo ',';
                    }
                ?>],
                borderWidth: 2
            }]
        };
        new Chart(statusCtx, {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Type Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeData = {
            labels: [<?php 
                $type_labels = [];
                $type_counts = [];
                while ($row = $type_stats->fetch_assoc()) {
                    $type_labels[] = "'" . ucfirst($row['property_type']) . "'";
                    $type_counts[] = $row['count'];
                }
                echo implode(',', $type_labels);
            ?>],
            datasets: [{
                label: 'Properties',
                data: [<?php echo implode(',', $type_counts); ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        };
        new Chart(typeCtx, {
            type: 'bar',
            data: typeData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
