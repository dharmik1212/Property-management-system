<?php
session_start();
require_once 'db.php';
check_login();

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['role'] === 'admin';

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

$date_condition = "DATE(p.created_at) BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types = 'ss';

if (!$is_admin) {
	$date_condition .= " AND p.user_id = ?";
	$params[] = $user_id;
	$types .= 'i';
}

// Totals
$total_query = "SELECT COUNT(*) as total_properties, SUM(price) as total_value, AVG(price) as avg_price 
				FROM properties p WHERE $date_condition";
$stmt = $conn->prepare($total_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();
$stmt->close();

// By status
$status_query = "SELECT status, COUNT(*) as count, SUM(price) as total_value 
				  FROM properties p WHERE $date_condition GROUP BY status";
$stmt = $conn->prepare($status_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$status_stats = $stmt->get_result();

// By type
$type_query = "SELECT property_type, COUNT(*) as count, SUM(price) as total_value, AVG(price) as avg_price 
			   FROM properties p WHERE $date_condition GROUP BY property_type";
$stmt2 = $conn->prepare($type_query);
$stmt2->bind_param($types, ...$params);
$stmt2->execute();
$type_stats = $stmt2->get_result();

// Top properties
$top_query = "SELECT p.*, u.username as owner_name 
			  FROM properties p LEFT JOIN users u ON p.user_id = u.id 
			  WHERE $date_condition ORDER BY p.price DESC LIMIT 50";
$stmt3 = $conn->prepare($top_query);
$stmt3->bind_param($types, ...$params);
$stmt3->execute();
$top_properties = $stmt3->get_result();

// Build CSV
$filename = 'financial_report_' . $date_from . '_to_' . $date_to . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// Header
fputcsv($out, ['Financial Report']);
fputcsv($out, ['Date Range', $date_from . ' to ' . $date_to]);
fputcsv($out, ['Generated At', date('c')]);
fputcsv($out, []);

// Summary
fputcsv($out, ['Summary']);
fputcsv($out, ['Total Properties', (int)$totals['total_properties']]);
fputcsv($out, ['Total Value', number_format((float)$totals['total_value'], 2)]);
fputcsv($out, ['Average Price', number_format((float)$totals['avg_price'], 2)]);
fputcsv($out, []);

// Status breakdown
fputcsv($out, ['By Status']);
fputcsv($out, ['Status', 'Count', 'Total Value']);
$status_stats->data_seek(0);
while ($row = $status_stats->fetch_assoc()) {
	fputcsv($out, [ucfirst($row['status']), (int)$row['count'], number_format((float)$row['total_value'], 2)]);
}
fputcsv($out, []);

// Type breakdown
fputcsv($out, ['By Property Type']);
fputcsv($out, ['Type', 'Count', 'Total Value', 'Average Price']);
while ($row = $type_stats->fetch_assoc()) {
	fputcsv($out, [ucfirst($row['property_type']), (int)$row['count'], number_format((float)$row['total_value'], 2), number_format((float)$row['avg_price'], 2)]);
}
fputcsv($out, []);

// Top properties
fputcsv($out, ['Top Properties']);
fputcsv($out, ['ID','Title','Location','Type','Status','Price','Owner','Created']);
while ($row = $top_properties->fetch_assoc()) {
	fputcsv($out, [
		$row['id'],
		$row['title'],
		$row['location'],
		$row['property_type'],
		$row['status'],
		number_format((float)$row['price'], 2),
		$row['owner_name'],
		date('Y-m-d', strtotime($row['created_at']))
	]);
}

fclose($out);

$stmt->close();
$stmt2->close();
$stmt3->close();
$conn->close();
exit();
