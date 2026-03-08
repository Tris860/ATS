<?php
$servername = "localhost"; // lowercase 'localhost'
$username   = "trishuba";
$password   = "}OzH@Db^o2f0";
$dbname     = "trishuba_atsfinal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully<br>";

// Test insert into users
$sql = "INSERT INTO users (email, role, passkey, status, timetable_enabled, hard_switch_enabled, timezone, hardware_id)
        VALUES ('debuguser@example.com', 'Admin', 'debugpass123', 1, 1, 0, 'Africa/Kigali', NULL)";
if ($conn->query($sql) === TRUE) {
    echo "✅ Insert into users successful<br>";
} else {
    echo "❌ Insert error: " . $conn->error . "<br>";
}

// Test select from users
$result = $conn->query("SELECT id_users, email, role, status, created_at FROM users ORDER BY id_users DESC LIMIT 5");
if ($result) {
    echo "✅ Select successful, showing last 5 rows:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id_users"] .
            " | Email: " . $row["email"] .
            " | Role: " . $row["role"] .
            " | Status: " . $row["status"] .
            " | Created: " . $row["created_at"] . "<br>";
    }
} else {
    echo "❌ Select error: " . $conn->error . "<br>";
}

$conn->close();
?>