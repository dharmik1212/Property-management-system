<?php
session_start();
require_once 'db.php';
check_login();

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

// Get available fields
$available_fields = [
    'id' => 'Property ID',
    'title' => 'Title',
    'description' => 'Description',
    'location' => 'Location',
    'price' => 'Price',
    'property_type' => 'Property Type',
    'status' => 'Status',
    'created_at' => 'Created Date',
    'updated_at' => 'Updated Date',
    'owner_name' => 'Owner Name',
    'owner_email' => 'Owner Email'
];

// Get filter options
$status_options = ['available', 'sold', 'rented'];
$type_options = ['house', 'apartment', 'condo', 'land'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_fields = isset($_POST['fields']) ? $_POST['fields'] : [];
    $status_filter = isset($_POST['status']) ? $_POST['status'] : '';
    $type_filter = isset($_POST['type']) ? $_POST['type'] : '';
    $price_min = isset($_POST['price_min']) ? floatval($_POST['price_min']) : '';
    $price_max = isset($_POST['price_max']) ? floatval($_POST['price_max']) : '';
    $date_from = isset($_POST['date_from']) ? $_POST['date_from'] : '';
    $date_to = isset($_POST['date_to']) ? $_POST['date_to'] : '';
    $export_format = isset($_POST['export_format']) ? $_POST['export_format'] : 'excel';
    
    if (empty($selected_fields)) {
        $_SESSION['error'] = "Please select at least one field to export.";
    } else {
        // Build query
        $field_list = implode(', ', array_map(function($field) {
            return $field === 'owner_name' ? 'u.username as owner_name' : 
                   ($field === 'owner_email' ? 'u.email as owner_email' : "p.$field");
        }, $selected_fields));
        
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
        
        $query = "SELECT $field_list FROM properties p LEFT JOIN users u ON p.user_id = u.id $where_clause ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($export_format === 'excel') {
            // Export to Excel
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="custom_property_export_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Cache-Control: max-age=0');
            
            // Output headers
            $headers = array_map(function($field) use ($available_fields) {
                return $available_fields[$field];
            }, $selected_fields);
            echo implode(',', $headers) . "\n";
            
            // Output data
            while ($row = $result->fetch_assoc()) {
                $csv_line = [];
                foreach ($selected_fields as $field) {
                    $value = $row[$field] ?? '';
                    $csv_line[] = '"' . str_replace('"', '""', $value) . '"';
                }
                echo implode(',', $csv_line) . "\n";
            }
        } else {
            // Export to PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="custom_property_export_' . date('Y-m-d_H-i-s') . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            
            // Generate PDF content
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Custom Property Export</title>';
            $html .= '<style>body{font-family:Arial,sans-serif;margin:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style></head><body>';
            $html .= '<h1>Custom Property Export</h1><p>Generated on: ' . date('F d, Y \a\t g:i A') . '</p>';
            $html .= '<table><thead><tr>';
            
            foreach ($selected_fields as $field) {
                $html .= '<th>' . $available_fields[$field] . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>';
                foreach ($selected_fields as $field) {
                    $value = $row[$field] ?? '';
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table></body></html>';
            echo $html;
        }
        
        $stmt->close();
        $conn->close();
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Export - Property Management</title>
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
                            <a class="nav-link active" href="export_custom.php">
                                <i class="fas fa-cog me-2"></i>Custom Export
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Custom Export</h1>
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
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cog me-2"></i>Configure Your Export
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Field Selection -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6><i class="fas fa-list me-2"></i>Select Fields to Export</h6>
                                    <div class="row">
                                        <?php foreach ($available_fields as $field => $label): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="fields[]" 
                                                       value="<?php echo $field; ?>" id="field_<?php echo $field; ?>"
                                                       <?php echo in_array($field, ['id', 'title', 'price', 'status']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="field_<?php echo $field; ?>">
                                                    <?php echo $label; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6><i class="fas fa-filter me-2"></i>Apply Filters (Optional)</h6>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status">
                                        <option value="">All Statuses</option>
                                        <?php foreach ($status_options as $status): ?>
                                        <option value="<?php echo $status; ?>"><?php echo ucfirst($status); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="type" class="form-label">Property Type</label>
                                    <select class="form-select" name="type" id="type">
                                        <option value="">All Types</option>
                                        <?php foreach ($type_options as $type): ?>
                                        <option value="<?php echo $type; ?>"><?php echo ucfirst($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="price_min" class="form-label">Min Price</label>
                                    <input type="number" class="form-control" name="price_min" id="price_min" 
                                           placeholder="0" min="0" step="0.01">
                                </div>
                                <div class="col-md-3">
                                    <label for="price_max" class="form-label">Max Price</label>
                                    <input type="number" class="form-control" name="price_max" id="price_max" 
                                           placeholder="1000000" min="0" step="0.01">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" name="date_from" id="date_from">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" name="date_to" id="date_to">
                                </div>
                            </div>

                            <!-- Export Format -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="export_format" class="form-label">Export Format</label>
                                    <select class="form-select" name="export_format" id="export_format" required>
                                        <option value="excel">Excel (CSV)</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="reports.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Reports
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-download me-2"></i>Generate Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>Export Help
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Field Descriptions:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Property ID:</strong> Unique identifier for each property</li>
                                    <li><strong>Title:</strong> Property title/name</li>
                                    <li><strong>Description:</strong> Detailed property description</li>
                                    <li><strong>Location:</strong> Property address/location</li>
                                    <li><strong>Price:</strong> Property price in dollars</li>
                                    <li><strong>Property Type:</strong> House, Apartment, Condo, or Land</li>
                                    <li><strong>Status:</strong> Available, Sold, or Rented</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Export Tips:</h6>
                                <ul class="list-unstyled">
                                    <li>• Select only the fields you need to keep the file size manageable</li>
                                    <li>• Use filters to export specific data subsets</li>
                                    <li>• CSV format is best for data analysis in Excel</li>
                                    <li>• PDF format is best for sharing and printing</li>
                                    <li>• Large exports may take a few moments to generate</li>
                                </ul>
                            </div>
                        </div>
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
                    
                    // Check if at least one field is selected
                    const selectedFields = form.querySelectorAll('input[name="fields[]"]:checked');
                    if (selectedFields.length === 0) {
                        event.preventDefault();
                        alert('Please select at least one field to export.');
                        return;
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Quick select buttons
        document.addEventListener('DOMContentLoaded', function() {
            // Add quick select buttons
            const fieldSection = document.querySelector('.row.mb-4');
            const quickSelectDiv = document.createElement('div');
            quickSelectDiv.className = 'col-12 mb-3';
            quickSelectDiv.innerHTML = `
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary" onclick="selectAll()">Select All</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="selectNone()">Select None</button>
                    <button type="button" class="btn btn-outline-info" onclick="selectBasic()">Basic Info</button>
                </div>
            `;
            fieldSection.insertBefore(quickSelectDiv, fieldSection.firstChild);
        });

        function selectAll() {
            document.querySelectorAll('input[name="fields[]"]').forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function selectNone() {
            document.querySelectorAll('input[name="fields[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        function selectBasic() {
            const basicFields = ['id', 'title', 'price', 'status', 'location'];
            document.querySelectorAll('input[name="fields[]"]').forEach(checkbox => {
                checkbox.checked = basicFields.includes(checkbox.value);
            });
        }
    </script>
</body>
</html>
