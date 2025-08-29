<?php
session_start();
require_once 'db.php';
check_login();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

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

// Get summary statistics
$total_properties = $result->num_rows;
$total_value = 0;
$properties_data = [];

while ($row = $result->fetch_assoc()) {
    $total_value += $row['price'];
    $properties_data[] = $row;
}

$average_price = $total_properties > 0 ? $total_value / $total_properties : 0;

// Generate HTML content for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Property Management Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #333; margin: 0; }
        .header p { color: #666; margin: 5px 0; }
        .summary { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 30px; }
        .summary-grid { display: flex; justify-content: space-between; }
        .summary-item { text-align: center; }
        .summary-item h3 { margin: 0; color: #333; }
        .summary-item p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
        .status-available { color: green; font-weight: bold; }
        .status-sold { color: red; font-weight: bold; }
        .status-rented { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Property Management Report</h1>
        <p>Generated on: ' . date('F d, Y \a\t g:i A') . '</p>
        <p>Report Type: ' . ($is_admin ? 'Administrator Report' : 'User Report') . '</p>
    </div>

    <div class="summary">
        <h2>Summary Statistics</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>' . $total_properties . '</h3>
                <p>Total Properties</p>
            </div>
            <div class="summary-item">
                <h3>$' . number_format($total_value, 2) . '</h3>
                <p>Total Value</p>
            </div>
            <div class="summary-item">
                <h3>$' . number_format($average_price, 2) . '</h3>
                <p>Average Price</p>
            </div>
        </div>
    </div>

    <h2>Property Details</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Location</th>
                <th>Price</th>
                <th>Type</th>
                <th>Status</th>
                <th>Owner</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>';

foreach ($properties_data as $property) {
    $status_class = 'status-' . $property['status'];
    $html .= '
            <tr>
                <td>' . $property['id'] . '</td>
                <td>' . htmlspecialchars($property['title']) . '</td>
                <td>' . htmlspecialchars($property['location']) . '</td>
                <td>$' . number_format($property['price'], 2) . '</td>
                <td>' . ucfirst($property['property_type']) . '</td>
                <td class="' . $status_class . '">' . ucfirst($property['status']) . '</td>
                <td>' . htmlspecialchars($property['owner_name']) . '</td>
                <td>' . date('M d, Y', strtotime($property['created_at'])) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated by the Property Management System</p>
        <p>For questions or support, please contact the system administrator</p>
    </div>
</body>
</html>';

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="property_report_' . date('Y-m-d') . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// For a production environment, you should use a proper HTML to PDF library like:
// - TCPDF
// - mPDF
// - Dompdf
// - wkhtmltopdf

// For now, we'll output the HTML content
// In a real implementation, you would convert this HTML to PDF using one of the libraries above

echo $html;

$stmt->close();
$conn->close();
exit();
?>
