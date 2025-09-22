<?php

class User {
    private $conn;

    /**
     * Constructor for the User class.
     *
     * @param mysqli $conn The database connection object.
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    /**
     * Registers a new user.
     *
     * @param string $email The user's email.
     * @param string $passkey The user's password (will be hashed).
     * @return array Success status and message.
     */
    public function register(string $email, string $passkey): array {
        if (empty($email) || empty($passkey)) {
            return ["success" => false, "message" => "Email and password cannot be empty."];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["success" => false, "message" => "Invalid email format."];
        }

        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id_users FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return ["success" => false, "message" => "Email already registered."];
        }
        $stmt->close();

        $hashedPasskey = password_hash($passkey, PASSWORD_DEFAULT);

        // Insert new user with default settings for timetable_enabled and hard_switch_enabled
        $stmt = $this->conn->prepare("INSERT INTO users (email, passkey, timetable_enabled, hard_switch_enabled) VALUES (?, ?, 1, 1)");
        $stmt->bind_param("ss", $email, $hashedPasskey);

        if ($stmt->execute()) {
            $stmt->close();
            return ["success" => true, "message" => "Registration successful!"];
        } else {
            error_log("User registration error: " . $stmt->error);
            $stmt->close();
            return ["success" => false, "message" => "Registration failed. Please try again."];
        }
    }

    /**
     * Logs in a user.
     *
     * @param string $email The user's email.
     * @param string $passkey The user's password.
     * @return array Success status and message. Sets session variables on success.
     */
    public function login(string $email, string $passkey): array {
        if (empty($email) || empty($passkey)) {
            return ["success" => false, "message" => "Email and password cannot be empty."];
        }

        $stmt = $this->conn->prepare("SELECT id_users, email, passkey, timetable_enabled, hard_switch_enabled FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($passkey, $user['passkey'])) {
                // Set session variables
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id_users'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['timetable_enabled'] = (bool)$user['timetable_enabled'];
                $_SESSION['hard_switch_enabled'] = (bool)$user['hard_switch_enabled'];

                $stmt->close();
                return ["success" => true, "message" => "Login successful!"];
            } else {
                $stmt->close();
                return ["success" => false, "message" => "Invalid email or password."];
            }
        } else {
            $stmt->close();
            return ["success" => false, "message" => "Invalid email or password."];
        }
    }

    /**
     * Logs out the current user.
     *
     * @return array Success status and message. Destroys session.
     */
    public function logout(): array {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session
        session_destroy();

        // Also clear the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        return ["success" => true, "message" => "Logged out successfully."];
    }

    /**
     * Changes the user's password.
     *
     * @param int $userId The ID of the user.
     * @param string $currentPassword The current password.
     * @param string $newPassword The new password.
     * @return array Success status and message.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array {
        if (empty($currentPassword) || empty($newPassword)) {
            return ["success" => false, "message" => "Current and new passwords cannot be empty."];
        }
        if (strlen($newPassword) < 8) {
            return ["success" => false, "message" => "New password must be at least 8 characters long."];
        }

        $stmt = $this->conn->prepare("SELECT passkey FROM users WHERE id_users = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($currentPassword, $user['passkey'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $this->conn->prepare("UPDATE users SET passkey = ? WHERE id_users = ?");
                $updateStmt->bind_param("si", $hashedNewPassword, $userId);
                if ($updateStmt->execute()) {
                    $updateStmt->close();
                    return ["success" => true, "message" => "Password changed successfully."];
                } else {
                    error_log("Password update error: " . $updateStmt->error);
                    $updateStmt->close();
                    return ["success" => false, "message" => "Failed to change password."];
                }
            } else {
                $stmt->close();
                return ["success" => false, "message" => "Incorrect current password."];
            }
        } else {
            $stmt->close();
            return ["success" => false, "message" => "User not found."];
        }
    }

    /**
     * Changes the user's email.
     *
     * @param int $userId The ID of the user.
     * @param string $currentPassword The user's current password for verification.
     * @param string $newEmail The new email address.
     * @return array Success status and message.
     */
    public function changeEmail(int $userId, string $currentPassword, string $newEmail): array {
        if (empty($currentPassword) || empty($newEmail)) {
            return ["success" => false, "message" => "Current password and new email cannot be empty."];
        }
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return ["success" => false, "message" => "Invalid new email format."];
        }

        // Verify current password and fetch existing email
        $stmt = $this->conn->prepare("SELECT passkey, email FROM users WHERE id_users = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($currentPassword, $user['passkey'])) {
                if ($user['email'] === $newEmail) {
                    $stmt->close();
                    return ["success" => false, "message" => "New email is the same as the current email."];
                }

                // Check if new email already exists for another user
                $checkEmailStmt = $this->conn->prepare("SELECT id_users FROM users WHERE email = ? AND id_users != ?");
                $checkEmailStmt->bind_param("si", $newEmail, $userId);
                $checkEmailStmt->execute();
                $checkEmailStmt->store_result();
                if ($checkEmailStmt->num_rows > 0) {
                    $checkEmailStmt->close();
                    $stmt->close();
                    return ["success" => false, "message" => "This email is already in use by another account."];
                }
                $checkEmailStmt->close();

                // Update email
                $updateStmt = $this->conn->prepare("UPDATE users SET email = ? WHERE id_users = ?");
                $updateStmt->bind_param("si", $newEmail, $userId);
                if ($updateStmt->execute()) {
                    // Update session email if successful
                    $_SESSION['email'] = $newEmail;
                    $updateStmt->close();
                    $stmt->close();
                    return ["success" => true, "message" => "Email changed successfully."];
                } else {
                    error_log("Email update error: " . $updateStmt->error);
                    $updateStmt->close();
                    $stmt->close();
                    return ["success" => false, "message" => "Failed to change email."];
                }
            } else {
                $stmt->close();
                return ["success" => false, "message" => "Incorrect current password."];
            }
        } else {
            $stmt->close();
            return ["success" => false, "message" => "User not found."];
        }
    }

    /**
     * Toggles the timetable visibility setting for a user.
     *
     * @param int $userId The ID of the user.
     * @param bool $enabled The desired state (true for enabled, false for disabled).
     * @return array Success status and message.
     */
    public function toggleTimetableVisibility(int $userId, bool $enabled): array {
        $intEnabled = $enabled ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE users SET timetable_enabled = ? WHERE id_users = ?");
        $stmt->bind_param("ii", $intEnabled, $userId);
        if ($stmt->execute()) {
            $_SESSION['timetable_enabled'] = $enabled; // Update session
            $stmt->close();
            return ["success" => true, "message" => "Timetable visibility updated."];
        } else {
            error_log("Timetable visibility update error: " . $stmt->error);
            $stmt->close();
            return ["success" => false, "message" => "Failed to update timetable visibility."];
        }
    }
     
    /**
     * Toggles the hard switch setting for a user.
     *
     * @param int $userId The ID of the user.
     * @param bool $enabled The desired state (true for enabled, false for disabled).
     * @return array Success status and message.
     */
    public function toggleHardSwitch(int $userId, bool $enabled): array {
        $intEnabled = $enabled ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE users SET hard_switch_enabled = ? WHERE id_users = ?");
        $stmt->bind_param("ii", $intEnabled, $userId);
        if ($stmt->execute()) {
            $_SESSION['hard_switch_enabled'] = $enabled; // Update session
            $stmt->close();
            return ["success" => true, "message" => "Hard switch updated. ", "state" => $enabled ];
        } else {
            error_log("Hard switch update error: " . $stmt->error);
            $stmt->close();
            return ["success" => false, "message" => "Failed to update hard switch."];
        }
    }

    /**
     * Gets user settings (email, timetable_enabled, hard_switch_enabled).
     *
     * @param int $userId The ID of the user.
     * @return array|null User settings or null if user not found.
     */
    public function getTimezone(int $userId){
        $stmt = $this->conn->prepare("SELECT timezone FROM users WHERE id_users = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        $stmt->close();

        return ["success" => true, "message" => $settings['timezone'] ?? null];
    }
    public function saveTimezone(int $userId, string $timezone): array {
       // 1. Prepare the SQL statement to update the timezone for a specific user.
       // The '?' are placeholders to prevent SQL injection.
       $stmt = $this->conn->prepare("UPDATE users SET timezone = ? WHERE id_users = ?");

       // 2. Bind the parameters to the placeholders.
      // 's' for the string ($timezone) and 'i' for the integer ($userId).
       $stmt->bind_param("si", $timezone, $userId);

       // 3. Execute the statement and check for success.
       if ($stmt->execute()) {
           $stmt->close();
           return ["success" => true, "message" => "Timezone updated successfully."];
        } else {
           // Log the specific error for debugging purposes.
           error_log("Timezone update error: " . $stmt->error);
           $stmt->close();
            return ["success" => false, "message" => "Failed to update timezone."];
        }
}
    public function getUserSettings(int $userId): ?array {
        $stmt = $this->conn->prepare("SELECT email, timetable_enabled, hard_switch_enabled FROM users WHERE id_users = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        $stmt->close();

        if ($settings) {
            $settings['timetable_enabled'] = (bool)$settings['timetable_enabled'];
            $settings['hard_switch_enabled'] = (bool)$settings['hard_switch_enabled'];
        }
        return $settings;
    }
}

// TimetableManager.php

// Ensure that the DateTime class is available for time validation.
// No other specific 'use' statements are needed for this class with standard PHP functions.

class TimetableManager {
    private $conn; // Stores the mysqli connection object

    /**
     * Constructor for TimetableManager.
     * Initializes the database connection and sets character set.
     *
     * @param mysqli $conn The mysqli database connection object.
     * @throws Exception If the database connection is not valid or charset cannot be set.
     */
    public function __construct(mysqli $conn) {
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        $this->conn = $conn;
        // Set charset for the connection to prevent encoding issues
        if (!$this->conn->set_charset("utf8mb4")) {
            throw new Exception("Error loading character set utf8mb4: " . $this->conn->error);
        }
    }

    /**
     * Fetches all periods, optionally filtered by day.
     *
     * @param string|null $dayOfWeek Optional. The day of the week to filter by (e.g., 'Monday').
     * @return array An array of period data.
     * @throws mysqli_sql_exception If a database error occurs during statement preparation or execution.
     */
    public function getAllPeriods(?string $dayOfWeek = null): array {
        $periods = [];
        $sql = "SELECT id, name, day_of_week, start_time, end_time, active FROM periods";
        $params = [];
        $types = "";

        // Conditionally add WHERE clause if a specific day is requested
        if ($dayOfWeek && $dayOfWeek !== 'All Days') { // 'All Days' is a frontend concept, not a DB filter
            $sql .= " WHERE day_of_week = ?";
            $params[] = $dayOfWeek;
            $types .= "s"; // 's' for string type
        }
        $sql .= " ORDER BY start_time ASC, name ASC"; // Order by time then name for consistent display

        // --- DEBUG LOGGING ---
        error_log("DEBUG-TM: getAllPeriods - Generated SQL: " . $sql);
        error_log("DEBUG-TM: getAllPeriods - Parameters: " . print_r($params, true));
        error_log("DEBUG-TM: getAllPeriods - Types: " . $types);
        // --- END DEBUG LOGGING ---

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        // Dynamically bind parameters based on PHP version compatibility
        if (!empty($params)) {
            if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
                // For PHP 8.1 and newer: Use the spread operator directly
                $stmt->bind_param($types, ...$params);
            } else {
                // For PHP versions older than 8.1: Use call_user_func_array with references
                $bindParams = array_merge([$types], $this->refValues($params));
                call_user_func_array([$stmt, 'bind_param'], $bindParams);
            }
        }

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $numRows = $result->num_rows; // Get number of rows

        // --- DEBUG LOGGING ---
        error_log("DEBUG-TM: getAllPeriods - Query executed successfully. Rows found: " . $numRows);
        // --- END DEBUG LOGGING ---

        while ($row = $result->fetch_assoc()) {
            // Convert 'active' (TINYINT in DB) to boolean for frontend consistency
            $row['active'] = (bool)$row['active'];
            $periods[] = $row;
        }

        $stmt->close();
        return $periods;
    }

    /**
     * Adds a new period to the database.
     *
     * @param array $data An associative array containing period details:
     * 'name', 'day', 'startTime', 'endTime', 'active'.
     * @return int The ID of the newly inserted period.
     * @throws InvalidArgumentException If input data is invalid (e.g., missing fields, invalid format, logical errors).
     * @throws mysqli_sql_exception If a database error occurs during statement preparation or execution.
     */
    public function addPeriod(array $data): int {
        // 1. Validate incoming data (Domain Integrity)
        $requiredFields = ['name', 'day_of_week', 'start_time', 'end_time']; // Corrected field names
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new InvalidArgumentException("Missing or empty required field: " . $field);
            }
        }

        $name = trim($data['name']);
        $dayOfWeek = trim($data['day_of_week']); // Corrected field name
        $startTime = trim($data['start_time']);   // Corrected field name
        $endTime = trim($data['end_time']);     // Corrected field name
        // Default 'active' to true if not provided in data
        $active = isset($data['active']) ? (bool)$data['active'] : true;

        // Validate day_of_week against allowed ENUM values
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        if (!in_array($dayOfWeek, $validDays)) {
            throw new InvalidArgumentException("Invalid day of week: '{$dayOfWeek}'. Must be one of " . implode(', ', $validDays));
        }

        // Validate time format (HH:MM:SS) using regex
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $startTime) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $endTime)) {
            throw new InvalidArgumentException("Invalid time format. Please use HH:MM:SS (e.g., 09:00:00).");
        }

        // Validate logical order of start and end times
        $startDateTime = DateTime::createFromFormat('H:i:s', $startTime); // Changed to H:i:s
        $endDateTime = DateTime::createFromFormat('H:i:s', $endTime);     // Changed to H:i:s

        if (!$startDateTime || !$endDateTime) {
            throw new InvalidArgumentException("Could not parse time values. Ensure they are valid HH:MM:SS.");
        }
        if ($startDateTime >= $endDateTime) {
            throw new InvalidArgumentException("End time must be after start time.");
        }

        // 2. Prepare and execute SQL INSERT statement
        $sql = "INSERT INTO periods (name, day_of_week, start_time, end_time, active) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $intActive = $active ? 1 : 0; // Convert boolean to integer for DB storage
        $stmt->bind_param("ssssi", $name, $dayOfWeek, $startTime, $endTime, $intActive);

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Error adding period: " . $stmt->error);
        }

        $insertId = $stmt->insert_id; // Get the ID of the newly inserted row
        $stmt->close();
        return $insertId;
    }

    /**
     * Updates an existing period in the database.
     *
     * @param int $id The ID of the period to update.
     * @param array $data An associative array of fields to update (e.g., 'name', 'day_of_week', 'start_time', 'end_time', 'active').
     * @return bool True if the period was successfully updated (at least one row affected).
     * @throws InvalidArgumentException If input data is invalid.
     * @throws mysqli_sql_exception If a database error occurs.
     * @throws Exception If no record found with the given ID.
     */
    public function updatePeriod(int $id, array $data): bool {
        if (empty($data)) {
            throw new InvalidArgumentException("No data provided for update.");
        }
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid ID provided update{backend for update.");
        }

        // Fetch current period data to validate against existing values if only partial updates are sent
        $currentPeriod = $this->getPeriodById($id);
        if (!$currentPeriod) {
            throw new Exception("Period with ID {$id} not found for update.");
        }

        $updates = [];
        $params = [];
        $types = "";

        // Initialize variables with current values, then update if new data is provided
        $updatedName = $currentPeriod['name'];
        $updatedDay = $currentPeriod['day_of_week'];
        $updatedStartTime = $currentPeriod['start_time'];
        $updatedEndTime = $currentPeriod['end_time'];
        $updatedActive = (bool)$currentPeriod['active'];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') throw new InvalidArgumentException("Period name cannot be empty.");
            $updates[] = "name = ?";
            $params[] = $name;
            $types .= "s";
            $updatedName = $name;
        }
        if (isset($data['day_of_week'])) { // Corrected field name
            $dayOfWeek = trim($data['day_of_week']); // Corrected field name
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            if (!in_array($dayOfWeek, $validDays)) {
                throw new InvalidArgumentException("Invalid day of week: '{$dayOfWeek}'. Must be one of " . implode(', ', $validDays));
            }
            $updates[] = "day_of_week = ?";
            $params[] = $dayOfWeek;
            $types .= "s";
            $updatedDay = $dayOfWeek;
        }
        if (isset($data['start_time'])) { // Corrected field name
            $startTime = trim($data['start_time']); // Corrected field name
            // Validate for HH:MM:SS format
            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $startTime)) {
                throw new InvalidArgumentException("Invalid start time format. Please use HH:MM:SS (e.g., 09:00:00).");
            }
            $updates[] = "start_time = ?";
            $params[] = $startTime;
            $types .= "s";
            $updatedStartTime = $startTime;
        }
        if (isset($data['end_time'])) { // Corrected field name
            $endTime = trim($data['end_time']); // Corrected field name
            // Validate for HH:MM:SS format
            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $endTime)) {
                throw new InvalidArgumentException("Invalid end time format. Please use HH:MM:SS (e.g., 09:00:00).");
            }
            $updates[] = "end_time = ?";
            $params[] = $endTime;
            $types .= "s";
            $updatedEndTime = $endTime;
        }
        if (isset($data['active'])) {
            $active = (bool)$data['active'];
            $updates[] = "active = ?";
            $params[] = $active ? 1 : 0;
            $types .= "i";
            $updatedActive = $active;
        }

        // Re-validate logical time order with potentially updated values
        $startDateTime = DateTime::createFromFormat('H:i:s', $updatedStartTime); // Changed to H:i:s
        $endDateTime = DateTime::createFromFormat('H:i:s', $updatedEndTime);     // Changed to H:i:s
        if (!$startDateTime || !$endDateTime) {
             throw new InvalidArgumentException("Could not parse time values for validation after update. Ensure they are valid HH:MM:SS.");
        }
        if ($startDateTime >= $endDateTime) {
            throw new InvalidArgumentException("End time must be after start time.");
        }

        // Construct the SQL UPDATE statement
        $sql = "UPDATE periods SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        // Add the ID to the parameters for the WHERE clause and its type
        $params[] = $id;
        $types .= "i"; // 'i' for integer type for the ID

        // Dynamically bind parameters based on PHP version compatibility
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            $stmt->bind_param($types, ...$params);
        } else {
            $bindParams = array_merge([$types], $this->refValues($params));
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Error updating period: " . $stmt->error);
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        // If no rows were affected, it means the record wasn't found or no actual changes were made.
        // We already checked for existence, so 0 affected rows implies no changes were different from current.
        return $affectedRows > 0;
    }

    /**
     * Deletes a period from the database.
     *
     * @param int $id The ID of the period to delete.
     * @return bool True if the period was deleted.
     * @throws InvalidArgumentException If ID is invalid.
     * @throws mysqli_sql_exception If a database error occurs.
     * @throws Exception If no record found with the given ID.
     */
    public function deletePeriod(int $id): bool {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid ID provided for delete.");
        }

        $sql = "DELETE FROM periods WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        // Bind the ID parameter
        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Error deleting period: " . $stmt->error);
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        // If no rows were affected, the record was not found.
        if ($affectedRows === 0) {
            throw new Exception("Period with ID {$id} not found for deletion.");
        }
        return true;
    }

    /**
     * Helper function to get a single period by ID. Used internally for update validation.
     *
     * @param int $id The ID of the period.
     * @return array|null The period data as an associative array, or null if not found.
     * @throws mysqli_sql_exception If a database error occurs.
     */
    private function getPeriodById(int $id): ?array {
        $sql = "SELECT id, name, day_of_week, start_time, end_time, active FROM periods WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement for fetching single period: " . $this->conn->error);
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Failed to execute statement for fetching single period: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $period = $result->fetch_assoc(); // Fetch a single row

        $stmt->close();

        if ($period) {
            $period['active'] = (bool)$period['active']; // Convert active to boolean
        }
        return $period;
    }

    /**
     * Helper function to pass parameters by reference for bind_param.
     * This is necessary for PHP versions older than 8.1 when using call_user_func_array with bind_param.
     *
     * @param array $arr The array of parameters.
     * @return array The array with values passed by reference.
     */
    private function refValues(array $arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key]; // Create a reference to each value
        }
        return $refs;
    }

    /**
     * Destructor for TimetableManager.
     * Ensures the database connection is closed when the object is destroyed.
     */
    // public function __destruct() {
    //     // Check if the connection exists and is still active before trying to close it.
    //     if ($this->conn && $this->conn->ping()) {
    //         $this->conn->close();
    //     }
    // }
}

class Commander{
    private $conn=null;
    public function __construct($conn)
    {
        $this->conn=$conn;
    }
    public function updateTimetableVisibility(string $userEmail, bool $enabled): array {
        if (empty($userEmail)) {
            return ['success' => false, 'message' => 'User email is required.'];
        }

        try {
            $update_query = "UPDATE users SET timetable_enabled = ? WHERE email = ?";
            $stmt = $this->conn->prepare($update_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $intEnabled = $enabled ? 1 : 0;
            $stmt->bind_param("is", $intEnabled, $userEmail);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $_SESSION['timetable_enabled'] = $enabled; // Update session
                return ['success' => true, 'message' => 'Timetable visibility updated successfully.'];
            } else {
                // This might happen if the user doesn't exist or no change was made
                return ['success' => false, 'message' => 'Failed to update timetable visibility or no change made.'];
            }
        } catch (Exception $e) {
            error_log("Update timetable visibility error for user email {$userEmail}: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating timetable visibility.'];
        }
    }

    /**
     * Updates the hard switch status for a user.
     *
     * @param string $userEmail The email of the user.
     * @param bool $enabled True to enable, false to disable.
     * @return array Success/error message.
     */
    public function updateHardSwitchStatus(string $userEmail, bool $enabled): array {
        if (empty($userEmail)) {
            return ['success' => false, 'message' => 'User email is required.'];
        }

        try {
            $update_query = "UPDATE users SET hard_switch_enabled = ? WHERE email = ?";
            $stmt = $this->conn->prepare($update_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $intEnabled = $enabled ? 1 : 0;
            $stmt->bind_param("is", $intEnabled, $userEmail);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $_SESSION['hard_switch_enabled'] = $enabled; // Update session
                return ['success' => true, 'message' => 'Hard switch status updated successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to update hard switch status or no change made.'];
            }
        } catch (Exception $e) {
            error_log("Update hard switch status error for user email {$userEmail}: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while updating hard switch status.'];
        }
    }
}

class TimeChecker{
    private $conn;

    /**
     * Constructor for the User class.
     *
     * @param mysqli $conn The database connection object.
     */
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }
    public function isCurrentTimeInPeriod() {
        $role = 'Admin'; // Replace with a dynamic user ID
        $stmt_timezone = $this->conn->prepare("SELECT timezone FROM users WHERE role = ?");
        $stmt_timezone->bind_param('s', $role);
        $stmt_timezone->execute();
        $result_timezone = $stmt_timezone->get_result();
        $userTimezone = $result_timezone->fetch_assoc()['timezone'] ?? 'Africa/Kigali';
        $stmt_timezone->close();
        date_default_timezone_set($userTimezone);
        $currentDay = date('l'); // Get current day of the week (e.g., 'Monday')
        $currentTime = date('H:i'); // Get current time in HH:MM:SS format
        $response["success"] = false;
        $response["message"] = "No active periods found for today.";
        try {
             // 1. Check user visibility
             $stmt_users = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE timetable_enabled = 1 OR hard_switch_enabled = 1");
             $stmt_users->execute();
             $result_users = $stmt_users->get_result();
             $count = $result_users->fetch_row()[0];
    
            if ($count > 0) {
               // 2. If user is enabled, check the time periods
               $currentDay = strtolower(date('l'));
               $currentTime = date('H:i');
        
               $stmt_periods = $this->conn->prepare("SELECT name, start_time FROM periods WHERE day_of_week = ? AND active = 1");
               $stmt_periods->bind_param('s', $currentDay);
               $stmt_periods->execute();
               $result_periods = $stmt_periods->get_result();

               while ($period = $result_periods->fetch_assoc()) {
                $db_start_time = date('H:i', strtotime($period['start_time']));
                  if ($currentTime == $db_start_time) {
                    $response["success"] = true;
                    $response["message"] = "It's time for period: " . $period['name'];
                    break; // Exit the loop once a match is found
                    }
                 }
                 
             }
        } catch (Exception $e) {
          // Handle database errors
          $response['status'] = 'error';
          $response['message'] = "Database error: " . $e->getMessage();
        } 
        return $response;
    }       
}
?>