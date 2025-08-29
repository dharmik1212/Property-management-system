<?php
session_start();
require_once 'db.php';
check_login();

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

// Get date range filters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today

// Build date condition
$date_condition = "DATE(p.created_at) BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types = 'ss';

if (!$is_admin) {
    $date_condition .= " AND p.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

// Total properties and value in date range
$total_query = "SELECT COUNT(*) as total_properties, SUM(price) as total_value, AVG(price) as avg_price 
                FROM properties p 
                WHERE $date_condition";
$stmt = $conn->prepare($total_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// Properties by status in date range
$status_query = "SELECT status, COUNT(*) as count, SUM(price) as total_value 
                 FROM properties p 
                 WHERE $date_condition 
                 GROUP BY status";
$stmt = $conn->prepare($status_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$status_stats = $stmt->get_result();

// Properties by type in date range
$type_query = "SELECT property_type, COUNT(*) as count, SUM(price) as total_value, AVG(price) as avg_price 
               FROM properties p 
               WHERE $date_condition 
               GROUP BY property_type";
$stmt = $conn->prepare($type_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$type_stats = $stmt->get_result();

// Monthly trends (last 12 months)
$monthly_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                  COUNT(*) as count, SUM(price) as total_value 
                  FROM properties p 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)";
if (!$is_admin) {
    $monthly_query .= " AND user_id = ?";
    $monthly_params = [$user_id];
    $monthly_types = 'i';
} else {
    $monthly_params = [];
    $monthly_types = '';
}
$monthly_query .= " GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month";
$stmt = $conn->prepare($monthly_query);
if (!empty($monthly_params)) {
    $stmt->bind_param($monthly_types, ...$monthly_params);
}
$stmt->execute();
$monthly_trends = $stmt->get_result();

// Top properties by value
$top_properties_query = "SELECT p.*, u.username as owner_name 
                         FROM properties p 
                         LEFT JOIN users u ON p.user_id = u.id 
                         WHERE $date_condition 
                         ORDER BY p.price DESC 
                         LIMIT 10";
$stmt = $conn->prepare($top_properties_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$top_properties = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report - Property Management</title>
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
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar me-2"></i>Reports & Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="financial_report.php">
                                <i class="fas fa-dollar-sign me-2"></i>Financial Report
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Financial Report</h1>
                    <div class="btn-group">
                        <a href="export_financial_excel.php?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-success">
                            <i class="fas fa-file-excel me-2"></i>Export to Excel
                        </a>
                        <a href="export_financial_pdf.php?date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-2"></i>Export to PDF
                        </a>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-2"></i>Apply Filter
                                    </button>
                                    <a href="financial_report.php" class="btn btn-secondary">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Financial Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Properties</h5>
                                        <h2 class="mb-0"><?php echo $totals['total_properties']; ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Value</h5>
                                        <h2 class="mb-0">$<?php echo number_format($totals['total_value'], 2); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Average Price</h5>
                                        <h2 class="mb-0">$<?php echo number_format($totals['avg_price'], 2); ?></h2>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Date Range</h5>
                                        <h6 class="mb-0"><?php echo date('M d', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?></h6>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calendar fa-2x"></i>
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
                                    <i class="fas fa-chart-pie me-2"></i>Value by Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusValueChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Monthly Trends
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyTrendChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Type Analysis -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-tags me-2"></i>Property Type Analysis
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Property Type</th>
                                                <th>Count</th>
                                                <th>Total Value</th>
                                                <th>Average Price</th>
                                                <th>Percentage of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($type = $type_stats->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo ucfirst($type['property_type']); ?></td>
                                                <td><?php echo $type['count']; ?></td>
                                                <td>$<?php echo number_format($type['total_value'], 2); ?></td>
                                                <td>$<?php echo number_format($type['avg_price'], 2); ?></td>
                                                <td><?php echo $totals['total_value'] > 0 ? number_format(($type['total_value'] / $totals['total_value']) * 100, 1) : 0; ?>%</td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Properties -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-trophy me-2"></i>Top Properties by Value
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Title</th>
                                                <th>Location</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Price</th>
                                                <th>Owner</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $rank = 1;
                                            while ($property = $top_properties->fetch_assoc()): 
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $rank; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($property['title']); ?></td>
                                                <td><?php echo htmlspecialchars($property['location']); ?></td>
                                                <td><?php echo ucfirst($property['property_type']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $property['status'] === 'available' ? 'success' : ($property['status'] === 'sold' ? 'danger' : 'warning'); ?>">
                                                        <?php echo ucfirst($property['status']); ?>
                                                    </span>
                                                </td>
                                                <td><strong>$<?php echo number_format($property['price'], 2); ?></strong></td>
                                                <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                                            </tr>
                                            <?php 
                                            $rank++;
                                            endwhile; 
                                            ?>
                                        </tbody>
                                    </table>
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
        // Status Value Chart
        const statusValueCtx = document.getElementById('statusValueChart').getContext('2d');
        const statusValueData = {
            labels: [<?php 
                $status_labels = [];
                $status_values = [];
                $status_colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
                $i = 0;
                $status_stats->data_seek(0);
                while ($row = $status_stats->fetch_assoc()) {
                    $status_labels[] = "'" . ucfirst($row['status']) . "'";
                    $status_values[] = $row['total_value'];
                    $i++;
                }
                echo implode(',', $status_labels);
            ?>],
            datasets: [{
                data: [<?php echo implode(',', $status_values); ?>],
                backgroundColor: [<?php 
                    for ($i = 0; $i < count($status_values); $i++) {
                        echo "'" . $status_colors[$i % count($status_colors)] . "'";
                        if ($i < count($status_values) - 1) echo ',';
                    }
                ?>],
                borderWidth: 2
            }]
        };
        new Chart(statusValueCtx, {
            type: 'doughnut',
            data: statusValueData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Trend Chart
        const monthlyCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        const monthlyData = {
            labels: [<?php 
                $month_labels = [];
                $month_values = [];
                while ($row = $monthly_trends->fetch_assoc()) {
                    $month_labels[] = "'" . date('M Y', strtotime($row['month'] . '-01')) . "'";
                    $month_values[] = $row['total_value'];
                }
                echo implode(',', $month_labels);
            ?>],
            datasets: [{
                label: 'Total Value ($)',
                data: [<?php echo implode(',', $month_values); ?>],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        };
        new Chart(monthlyCtx, {
            type: 'line',
            data: monthlyData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
