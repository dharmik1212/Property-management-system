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

// Properties
$list_query = "SELECT p.*, u.username as owner_name 
			  FROM properties p LEFT JOIN users u ON p.user_id = u.id 
			  WHERE $date_condition ORDER BY p.created_at DESC";
$stmt2 = $conn->prepare($list_query);
$stmt2->bind_param($types, ...$params);
$stmt2->execute();
$list = $stmt2->get_result();

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Financial Report</title>
<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
.h { font-size: 20px; font-weight: bold; }
.mb { margin-bottom: 10px; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border: 1px solid #ddd; padding: 6px; }
.table th { background: #f0f0f0; }
.small { color: #666; }
</style>
</head>
<body>
<div class="h mb">Financial Report</div>
<div class="mb small">Date Range: <?php echo htmlspecialchars($date_from); ?> to <?php echo htmlspecialchars($date_to); ?></div>
<div class="mb small">Generated: <?php echo date('Y-m-d H:i'); ?></div>

<table class="table mb">
<tr><th>Total Properties</th><th>Total Value</th><th>Average Price</th></tr>
<tr>
<td><?php echo (int)$totals['total_properties']; ?></td>
<td>$<?php echo number_format((float)$totals['total_value'], 2); ?></td>
<td>$<?php echo number_format((float)$totals['avg_price'], 2); ?></td>
</tr>
</table>

<table class="table">
<thead>
<tr><th>ID</th><th>Title</th><th>Location</th><th>Type</th><th>Status</th><th>Price</th><th>Owner</th><th>Created</th></tr>
</thead>
<tbody>
<?php while ($row = $list->fetch_assoc()): ?>
<tr>
<td><?php echo $row['id']; ?></td>
<td><?php echo htmlspecialchars($row['title']); ?></td>
<td><?php echo htmlspecialchars($row['location']); ?></td>
<td><?php echo ucfirst($row['property_type']); ?></td>
<td><?php echo ucfirst($row['status']); ?></td>
<td>$<?php echo number_format((float)$row['price'], 2); ?></td>
<td><?php echo htmlspecialchars($row['owner_name']); ?></td>
<td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</body>
</html>
<?php
$html = ob_get_clean();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="financial_report_' . $date_from . '_to_' . $date_to . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $html;

$stmt->close();
$stmt2->close();
$conn->close();
exit();
