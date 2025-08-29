<?php
session_start();
require_once 'db.php';
check_login();

// Set headers for Excel download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="properties_report_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

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

// Create Excel file using simple CSV format (since PHPSpreadsheet requires installation)
// For a production environment, you should install PHPSpreadsheet via Composer

// Create CSV content
$csv_content = "ID,Title,Description,Location,Price,Property Type,Status,Owner,Owner Email,Created Date,Updated Date\n";

while ($row = $result->fetch_assoc()) {
    $csv_line = array(
        $row['id'],
        '"' . str_replace('"', '""', $row['title']) . '"',
        '"' . str_replace('"', '""', $row['description']) . '"',
        '"' . str_replace('"', '""', $row['location']) . '"',
        $row['price'],
        $row['property_type'],
        $row['status'],
        '"' . str_replace('"', '""', $row['owner_name']) . '"',
        $row['owner_email'],
        $row['created_at'],
        $row['updated_at']
    );
    $csv_content .= implode(',', $csv_line) . "\n";
}

// Output CSV content
echo $csv_content;

$stmt->close();
$conn->close();
exit();
?>
