<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "./database/database.php"; 

try {
    // Connect to the database
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Test query to fetch a record from the iss_persons table
    $sql = "SELECT * FROM iss_persons LIMIT 1";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo "Database connection successful!<br>";
        echo "Sample Data from iss_persons Table:<br>";
        print_r($data);
    } else {
        echo "Database connection successful, but no data found in iss_persons table.";
    }
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
} finally {
    // Disconnect from the database
    Database::disconnect();
}
?>