<?php
require_once 'db.php';

header('Content-Type: text/plain');

$email = 'admin@gmail.com';
$plain = 'admin@123';

$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "USER_NOT_FOUND\n";
    exit;
}

$user = $res->fetch_assoc();

echo "FOUND id=".$user['id']." username=".$user['username']." email=".$user['email']." role=".$user['role']."\n";

echo "HASH=".$user['password']."\n";

echo "password_verify result: ";
var_dump(password_verify($plain, $user['password']));

echo "\n";

$conn->close();
