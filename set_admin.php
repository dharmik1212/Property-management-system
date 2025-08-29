<?php
require_once 'db.php';

header('Content-Type: text/plain');

$email = 'admin@gmail.com';
$plain = 'admin@123';
$hash = password_hash($plain, PASSWORD_BCRYPT);

echo "New hash length=".strlen($hash)."\n";

$sql = "UPDATE users SET email=?, password=?, role='admin' WHERE username='admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $email, $hash);
if (!$stmt->execute()) {
    echo "UPDATE_FAILED: ".$stmt->error."\n";
} else {
    echo "UPDATED\n";
}

// Verify
$check = $conn->prepare("SELECT email, password, role FROM users WHERE username='admin'");
$check->execute();
$res = $check->get_result();
$row = $res->fetch_assoc();

echo "Stored email=".$row['email']."\n";
echo "Stored hash length=".strlen($row['password'])."\n";
echo "Verify result=".(password_verify($plain, $row['password'])?'OK':'BAD')."\n";

$conn->close();
