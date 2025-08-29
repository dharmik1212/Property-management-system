<?php
session_start();
require_once 'db.php';
check_login();

echo "<h2>Property Data Fix Script</h2>";

// Fix Property 2 (AMBANI) - Update to more realistic property type
$update_query1 = "UPDATE properties SET property_type = 'house' WHERE title = 'AMBANI' AND property_type = 'land'";
if ($conn->query($update_query1)) {
    echo "<p>✅ Fixed Property 2 (AMBANI): Changed type from 'land' to 'house'</p>";
} else {
    echo "<p>❌ Error updating Property 2: " . $conn->error . "</p>";
}

// Fix Property 3 (Dhamo) - Update to realistic price
$update_query2 = "UPDATE properties SET price = 999999.99 WHERE title = 'Dhamo' AND price > 99999999";
if ($conn->query($update_query2)) {
    echo "<p>✅ Fixed Property 3 (Dhamo): Updated price from $99,999,999.99 to $999,999.99</p>";
} else {
    echo "<p>❌ Error updating Property 3: " . $conn->error . "</p>";
}

// Add better descriptions to properties
$update_query3 = "UPDATE properties SET description = 'Modern apartment in Bakrol with excellent amenities and convenient location.' WHERE title = 'madhav' AND (description IS NULL OR description = '')";
if ($conn->query($update_query3)) {
    echo "<p>✅ Added description to Property 1 (madhav)</p>";
} else {
    echo "<p>❌ Error updating Property 1 description: " . $conn->error . "</p>";
}

$update_query4 = "UPDATE properties SET description = 'Luxury house in Mumbai with premium location and modern facilities.' WHERE title = 'AMBANI' AND (description IS NULL OR description = '')";
if ($conn->query($update_query4)) {
    echo "<p>✅ Added description to Property 2 (AMBANI)</p>";
} else {
    echo "<p>❌ Error updating Property 2 description: " . $conn->error . "</p>";
}

$update_query5 = "UPDATE properties SET description = 'Spacious property in Lala India with great potential for development.' WHERE title = 'Dhamo' AND (description IS NULL OR description = '')";
if ($conn->query($update_query5)) {
    echo "<p>✅ Added description to Property 3 (Dhamo)</p>";
} else {
    echo "<p>❌ Error updating Property 3 description: " . $conn->error . "</p>";
}

// Show current property data
echo "<h3>Current Property Data:</h3>";
$result = $conn->query("SELECT id, title, property_type, status, price, location, description FROM properties ORDER BY id");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Price</th><th>Location</th><th>Description</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . ucfirst($row['property_type']) . "</td>";
        echo "<td>" . ucfirst($row['status']) . "</td>";
        echo "<td>$" . number_format($row['price'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['description'], 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No properties found.</p>";
}

echo "<br><a href='property_report.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Property Report</a>";

$conn->close();
?>
