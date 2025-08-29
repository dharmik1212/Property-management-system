<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$email = sanitize_input($_POST['email']);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Please provide both email and password";
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
if (!$stmt) {
    $_SESSION['error'] = "System error. Please try again later.";
    header("Location: index.php");
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Invalid email or password";
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = "Invalid email or password";
    header("Location: index.php");
    exit();
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $email;
$_SESSION['role'] = $user['role'];

// Redirect based on role
if ($user['role'] === 'admin') {
    header("Location: dashboard_admin.php");
} else {
    header("Location: dashboard_client.php");
}
exit();
?>
