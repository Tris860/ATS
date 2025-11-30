
<?php
$servername = "localhost";
$username = "root";
$password = ""; // Your actual password
$dbname = "atsfinal"; // Your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    else{
        echo "good";
    }
?>