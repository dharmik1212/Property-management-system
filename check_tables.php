<?php
require_once 'db.php';

echo "<h2>Database Structure Check</h2>";

// Check if tables exist
$tables = $conn->query("SHOW TABLES");
echo "<h3>Existing Tables:</h3>";
if ($tables->num_rows > 0) {
    while($table = $tables->fetch_array()) {
        echo "<strong>" . $table[0] . "</strong><br>";
        // Show table structure
        $structure = $conn->query("DESCRIBE " . $table[0]);
        echo "<pre>";
        while($row = $structure->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre><hr>";
    }
} else {
    echo "No tables found in the database.<br>";
}

// Try to create tables if they don't exist
echo "<h3>Attempting to create/update tables:</h3>";
$schema = file_get_contents('schema.sql');
if ($schema === false) {
    echo "Could not read schema.sql file<br>";
} else {
    if ($conn->multi_query($schema)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        echo "Schema executed successfully<br>";
    } else {
        echo "Error executing schema: " . $conn->error . "<br>";
    }
}

$conn->close();
?> 