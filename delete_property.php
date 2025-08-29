<?php
session_start();
require_once 'db.php';
check_login();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No property specified for deletion";
    header("Location: dashboard_client.php");
    exit();
}

$property_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Check if property exists and belongs to user
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $property_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Property not found or access denied";
    header("Location: dashboard_client.php");
    exit();
}

// Delete the property
$delete_stmt = $conn->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?");
$delete_stmt->bind_param("ii", $property_id, $user_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = "Property deleted successfully";
} else {
    $_SESSION['error'] = "Error deleting property: " . $conn->error;
}

$delete_stmt->close();
$conn->close();

header("Location: dashboard_client.php");
exit();
?>
