<?php

class User
{
    private $conn;



    /**
     * Constructor for the User class.
     *
     * @param mysqli $conn The database connection object.
     */
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Registers a new user.
     *
     * @param string $email The user's email.
     * @param string $passkey The user's password (will be hashed).
     * @return array Success status and message.
     */
    public function register(string $email, string $passkey): array
    {
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
    private function check_Subscription(int $id): bool
    {
        $stmt = $this->conn->prepare("SELECT * FROM `subscriptions` WHERE id= ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if ($user['status'] == 0) {
            return False;
        }
        return True;
    }
    /**
     * Logs in a user.
     *
     * @param string $email The user's email.
     * @param string $passkey The user's password.
     * @return array Success status and message. Sets session variables on success.
     */
    public function login(string $email, string $passkey): array
    {
        if (empty($email) || empty($passkey)) {
            return ["success" => false, "message" => "Email and password cannot be empty."];
        }

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();


        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user["role"] == "Super Admin") {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id_users'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['timetable_enabled'] = (bool)$user['timetable_enabled'];
                $_SESSION['hard_switch_enabled'] = (bool)$user['hard_switch_enabled'];

                $stmt->close();
                return ["success" => true, "message" => "Login successful!", "role" => "Super Admin"];
            }

            if (password_verify($passkey, $user['passkey'])) {
                if (!$this->check_Subscription($user["id_users"])) {
                    return ["success" => false, "message" => "Your access to ATS features has been paused. 
                                                      To continue using our services without interruption,
                                                      please contact (+250 784 912 881) to renew your subscription."];
                }
                // Set session variables
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['id_users'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['timetable_enabled'] = (bool)$user['timetable_enabled'];
                $_SESSION['hard_switch_enabled'] = (bool)$user['hard_switch_enabled'];

                $stmt->close();
                return ["success" => true, "message" => "Login successful!", "role" => "Admin"];
            } else {
                $stmt->close();
                return ["success" => false, "message" => "Invalid email or password."];
            }
        } else {
            $stmt->close();
            return ["success" => false, "message" => "Invalid email or password."];
        }
    }
    public function WEMOS_AUTH(string $name, string $passkey)
    {
        if (empty($name) || empty($passkey)) {
            return ["success" => false, "message" => "name and password cannot be empty."];
        }

        $stmt = $this->conn->prepare("SELECT * FROM hardware WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $device = $result->fetch_assoc();
            if (password_verify($passkey, $device['passkey'])) {
                // return ["success" => false, "message" =>  $device['passkey']."Invalid name or password."];
                $stmt = $this->conn->prepare("SELECT timetable_enabled,hard_switch_enabled FROM users WHERE hardware_id = ?");
                $stmt->bind_param("i", $device['id']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    // Set session variables
                    $status = [];
                    $status['device_name'] = $device['name'];
                    $status['timetable_enabled'] = (bool)$user['timetable_enabled'];
                    $status['hard_switch_enabled'] = (bool)$user['hard_switch_enabled'];

                    $stmt->close();
                    return ["success" => true, "data" => $status, "message" => "Login successful!"];
                } else {
                    $stmt->close();
                    return ["success" => false, "message" => "No user assigned to this device."];
                }
            } else {
                $stmt->close();
                return ["success" => false, "message" => "Invalid password. Expected: " . $device['passkey']. " Provided: " . $passkey];
            }
        }
    }
    public function getAssignedDevice(string $email): ?string
    {
        if (empty($email)) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT hardware_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            $user['hardware_id'];

            $stmt = $this->conn->prepare("SELECT name FROM hardware WHERE id = ?");
            $stmt->bind_param("i", $user['hardware_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $device = $result->fetch_assoc();
            if ($result->num_rows === 1) {
                $stmt->close();
                return $device['name'];
            } else {
                $stmt->close();
                return null;
            }
        } else {
            $stmt->close();
            return null;
        }
    }


    /**
     * Logs out the current user.
     *
     * @return array Success status and message. Destroys session.
     */
    public function logout(): array
    {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session
        session_destroy();

        // Also clear the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
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
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
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
    public function changeEmail(int $userId, string $currentPassword, string $newEmail): array
    {
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
    public function toggleTimetableVisibility(int $userId, bool $enabled): array
    {
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
    public function toggleHardSwitch(int $userId, bool $enabled): array
    {
        $intEnabled = $enabled ? 1 : 0;
        $stmt = $this->conn->prepare("UPDATE users SET hard_switch_enabled = ? WHERE id_users = ?");
        $stmt->bind_param("ii", $intEnabled, $userId);
        if ($stmt->execute()) {
            $_SESSION['hard_switch_enabled'] = $enabled; // Update session
            $stmt->close();
            return ["success" => true, "message" => "Hard switch updated. ", "state" => $enabled];
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
    public function getTimezone(int $userId)
    {
        $stmt = $this->conn->prepare("SELECT timezone FROM users WHERE id_users = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        $stmt->close();

        return ["success" => true, "message" => $settings['timezone'] ?? null];
    }
    public function saveTimezone(int $userId, string $timezone): array
    {
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
    public function getUserSettings(int $userId): ?array
    {
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

class TimetableManager
{
    private $conn; // Stores the mysqli connection object

    /**
     * Constructor for TimetableManager.
     * Initializes the database connection and sets character set.
     *
     * @param mysqli $conn The mysqli database connection object.
     * @throws Exception If the database connection is not valid or charset cannot be set.
     */
    public function __construct(mysqli $conn)
    {
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
     * Helper function to pass parameters by reference for bind_param.
     * This is necessary for PHP versions older than 8.1 when using call_user_func_array with bind_param.
     *
     * @param array $arr The array of parameters.
     * @return array The array with values passed by reference.
     */
    private function refValues(array &$arr): array
    {
        $refs = [];
        foreach ($arr as $key => &$value) {
            $refs[$key] = &$value;
        }
        return $refs;
    }


    /**
     * Fetches all periods, optionally filtered by day.
     *
     * @param string|null $dayOfWeek Optional. The day of the week to filter by (e.g., 'Monday').
     * @return array An array of period data.
     * @throws mysqli_sql_exception If a database error occurs during statement preparation or execution.
     */
    public function getAllPeriods(string $owner, ?string $dayOfWeek = null)
    {
        $periods = [];
        $sql = "SELECT * FROM periods WHERE owner = ?";
        $params = [$owner];
        $types = "s";

        // Conditionally add AND clause if a specific day is requested
        if ($dayOfWeek && $dayOfWeek !== 'All Days') {
            $sql .= " AND day_of_week = ?";
            $params[] = $dayOfWeek;
            $types .= "s";
        }

        $sql .= " ORDER BY start_time ASC, name ASC";
        // Order by time then name for consistent display

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
    public function addPeriod(array $data, string $owner): int
    {
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
        $sql = "INSERT INTO periods (name, day_of_week, start_time, end_time, active,owner) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $intActive = $active ? 1 : 0; // Convert boolean to integer for DB storage
        $stmt->bind_param("ssssis", $name, $dayOfWeek, $startTime, $endTime, $intActive, $owner);

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
    public function updatePeriod(int $id, array $data): bool
    {
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
    public function deletePeriod(int $id): bool
    {
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
    private function getPeriodById(int $id): ?array
    {
        $sql = "SELECT * FROM periods WHERE id = ?";
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
            $period['current'] = (bool)$period['current']; // Convert current to boolean
            $period['active'] = (bool)$period['active']; // Convert active to boolean
        }
        return $period;
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

class Commander
{
    private $conn = null;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    public function updateTimetableVisibility(string $userEmail, bool $enabled): array
    {
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
    public function updateHardSwitchStatus(string $userEmail, bool $enabled): array
    {
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

class TimeChecker
{
    private $conn;

    /**
     * Constructor for the User class.
     *
     * @param mysqli $conn The database connection object.
     */
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }
    private function getDevice($owner)
    {
        $respient = [];

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $owner);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($user = $result->fetch_assoc()) {
            $query = $this->conn->prepare("SELECT id, name FROM hardware WHERE id = ?");
            $query->bind_param('i', $user['hardware_id']);
            $query->execute();
            $hardwareResult = $query->get_result();
            while ($hardware = $hardwareResult->fetch_assoc()) {
                $respient[] = $hardware;
            }
        }
        return $respient;
    }

    private function isCurrentTimeInPeriod()
    {
        $role = 'Admin'; // Replace with a dynamic user ID
        $stmt_timezone = $this->conn->prepare("SELECT timezone FROM users WHERE role = ?");
        $stmt_timezone->bind_param('s', $role);
        $stmt_timezone->execute();
        $result_timezone = $stmt_timezone->get_result();
        $userTimezone = $result_timezone->fetch_assoc()['timezone'] ?? 'Africa/Kigali';
        $stmt_timezone->close();

        date_default_timezone_set($userTimezone);
        $currentDay = strtolower(date('l'));
        $currentTime = date('H:i');

        $periods = []; // collect all matched periods

        try {
            // 1. Check if any users are enabled
            $stmt_users = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE timetable_enabled = 1");
            $stmt_users->execute();
            $result_users = $stmt_users->get_result();
            $count = $result_users->fetch_row()[0];

            if ($count > 0) {
                // 2. Fetch active periods for today
                $stmt_periods = $this->conn->prepare("SELECT * FROM periods WHERE day_of_week = ? AND active = 1");
                $stmt_periods->bind_param('s', $currentDay);
                $stmt_periods->execute();
                $result_periods = $stmt_periods->get_result();

                while ($period = $result_periods->fetch_assoc()) {
                    $db_start_time = date('H:i', strtotime($period['start_time']));
                    if ($currentTime == $db_start_time) {
                        $falseVal = 0;
                        $trueVal = 1;
                        $respient = $this->getDevice($period['owner']);

                        // If no devices found, add placeholder
                        if (empty($respient)) {
                            $respient[] = [
                                "id" => null,
                                "name" => "No device assigned to this timetable"
                            ];
                        }

                        $this->conn->begin_transaction();
                        try {
                            // Reset all periods for this owner
                            $stmt_false = $this->conn->prepare("UPDATE periods SET current = ? WHERE owner = ?");
                            $stmt_false->bind_param('is', $falseVal, $period['owner']); // assuming owner is stored as email (string)
                            $stmt_false->execute();
                            $stmt_false->close();

                            // Set this specific period to current = 1
                            $stmt_true = $this->conn->prepare("UPDATE periods SET current = ? WHERE id = ? AND owner = ?");
                            $stmt_true->bind_param('iis', $trueVal, $period['id'], $period['owner']);
                            $stmt_true->execute();
                            $stmt_true->close();

                            $this->conn->commit();

                            $periods[] = [
                                "period"  => $period['name'],
                                "id"      => $period['id'],
                                "message" => $period['name'],
                                "owner"   => $period['owner'],
                                "devices" => empty($respient) ? [
                                    ["id" => null, "name" => "No device assigned to this timetable"]
                                ] : $respient
                            ];
                        } catch (Exception $e) {
                            $this->conn->rollback();
                            $periods[] = [
                                "period"  => $period['name'],
                                "id"      => $period['id'],
                                "message" => "Failed to update current period: " . $e->getMessage(),
                                "owner"   => $period['owner'],
                                "devices" => $respient
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Database error: " . $e->getMessage()
            ];
        }

        // Final unified response
        if (!empty($periods)) {
            return [
                "success" => true,
                "periods" => $periods
            ];
        } else {
            return [
                "success" => false,
                "message" => "No active periods found for today."
            ];
        }
    }
    /**
     * Checks if the current time is within any active period for the user.
     *
     * @return array An array containing success status and period information.
     */
    public function checkCurrentTimeInPeriod()
    {
        return $this->isCurrentTimeInPeriod();
    }
    private function checkerSuperUser(int $id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM `users` WHERE id_users= ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if ($user['role'] == "Super Admin") {
            return True;
        }
        return False;
    }
    public function getAllUsers(int $id): array
    {
        // Check access
        if (!$this->checkerSuperUser($id)) {
            return ["success" => false, "message" => "No Access to this data" . " " . $id];
        }

        $results = [];

        // SQL query joining users and subscriptions
        $sql = "
        SELECT 
            users.email, 
            subscriptions.id AS subscription_id, 
            subscriptions.date_of_expiry, 
            subscriptions.status
        FROM users
        INNER JOIN subscriptions ON users.id_users = subscriptions.id
        WHERE users.id_users != ?
        ORDER BY subscriptions.date_of_expiry DESC
    ";

        // Prepare statement
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ["success" => false, "message" => "Query preparation failed: " . $this->conn->error];
        }

        // Bind parameters (compatible with older PHP versions)
        $params = [$id];
        $types = "i";

        $bindParams = [];
        $bindParams[] = $types;
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key]; // must be passed by reference
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);

        // Execute query
        if (!$stmt->execute()) {
            $stmt->close();
            return ["success" => false, "message" => "Query execution failed: " . $stmt->error];
        }

        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $results[] = [
                "email" => $row["email"],
                "subscription_id" => $row["subscription_id"],
                "date_of_expiry" => $row["date_of_expiry"],
                "status" => $row["status"]
            ];
        }

        $stmt->close();

        return ["success" => true, "data" => $results];
    }

    public function updateUserPassword(int $userId, string $newPassword): array
    {
        // Hash the new password securely
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);;

        if ($hashedPassword === false) {
            return ["success" => false, "message" => "Password hashing failed"];
        }

        // SQL to update the user's password
        $sql = "UPDATE users SET passkey = ? WHERE id_users = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ["success" => false, "message" => "Query preparation failed: " . $this->conn->error];
        }

        // Bind parameters
        $stmt->bind_param("si", $hashedPassword, $userId);

        // Execute
        if (!$stmt->execute()) {
            $stmt->close();
            return ["success" => false, "message" => "Password update failed: " . $stmt->error];
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            return ["success" => true, "message" => "Password updated successfully"];
        } else {
            return ["success" => false, "message" => "No user found with this ID"];
        }
    }
    public function getUsersWithSubscriptionByStatus(int $userStatus, ?int $excludeUserId = null, ?int $subscriptionStatus = null): array
    {
        $results = [];

        // Base SQL with JOIN to subscriptions
        $sql = "
        SELECT
            users.email,
            subscriptions.id AS subscription_id,
            subscriptions.date_of_expiry,
            subscriptions.status AS subscription_status
        FROM users
        INNER JOIN subscriptions ON users.id_users = subscriptions.id
        WHERE users.status = ?
    ";

        // Params/Types for older PHP compatibility (no splat)
        $params = [$userStatus];
        $types  = "i";

        // Optional: exclude a specific user
        if ($excludeUserId !== null) {
            $sql .= " AND users.id_users != ?";
            $params[] = $excludeUserId;
            $types   .= "i";
        }

        // Optional: filter by subscription status (e.g., 1 for active)
        if ($subscriptionStatus !== null) {
            $sql .= " AND subscriptions.status = ?";
            $params[] = $subscriptionStatus;
            $types   .= "i";
        }

        // Order by nearest expiry first
        $sql .= " ORDER BY subscriptions.date_of_expiry ASC";

        // Prepare
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ["success" => false, "message" => "Query preparation failed: " . $this->conn->error];
        }

        // Bind (compatible with older PHP versions)
        $bindParams = [$types];
        foreach ($params as $k => $v) {
            $bindParams[] = &$params[$k]; // pass by reference
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);

        // Execute
        if (!$stmt->execute()) {
            $stmt->close();
            return ["success" => false, "message" => "Query execution failed: " . $stmt->error];
        }

        // Fetch
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $results[] = [
                "email" => $row["email"],
                "id" => $row["subscription_id"],
                "date_of_expiry" => $row["date_of_expiry"],
                "status" => $row["subscription_status"]
            ];
        }

        $stmt->close();

        return ["success" => true, "data" => $results];
    }
}

class Administator
{
    private $conn;

    /**
     * Constructor for the Administrator class.
     *
     * @param mysqli $conn The database connection object.
     */
    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }
    public function getAllAdmins(): array
    {
        $admins = [];

        $sql = "SELECT * FROM users WHERE role IN ('Admin') ORDER BY id_users DESC";

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Failed to execute statement: " . $stmt->error);
        }

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }

        $stmt->close();
        return $admins;
    }
    public function createPlan(array $data): int
    {
        // Required fields
        $requiredFields = ['name', 'duration', 'price', 'type_plan', 'features', 'description'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new InvalidArgumentException("Missing or empty required field: " . $field);
            }
        }

        // Sanitize & assign
        $name            = trim($data['name']);
        $durationMonths  = (int)$data['duration'];
        $price           = (float)$data['price'];
        $typePlan        = trim($data['type_plan']);
        $features        = trim($data['features']);
        $description     = trim($data['description']);

        // Validation
        if ($durationMonths <= 0) {
            throw new InvalidArgumentException("Duration must be a positive integer.");
        }

        if ($price < 0) {
            throw new InvalidArgumentException("Price cannot be negative.");
        }

        // SQL Insert
        $sql = "INSERT INTO subscription_plan (name, duration, price, type_plan, features, description)
            VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        // Bind parameters: s = string, i = integer, d = double
        $stmt->bind_param(
            "sidsss",
            $name,
            $durationMonths,
            $price,
            $typePlan,
            $features,
            $description
        );

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Error adding plan: " . $stmt->error);
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }
    public function updatePlan(int $id, array $data): bool
    {
        // Required fields for update
        $requiredFields = ['name', 'duration', 'price', 'type_plan', 'features', 'description'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new InvalidArgumentException("Missing or empty required field: " . $field);
            }
        }

        // Sanitize
        $name            = trim($data['name']);
        $durationMonths  = (int)$data['duration'];
        $price           = (float)$data['price'];
        $typePlan        = trim($data['type_plan']);
        $features        = trim($data['features']);
        $description     = trim($data['description']);

        if ($durationMonths <= 0) {
            throw new InvalidArgumentException("Duration must be a positive integer." . $durationMonths . "hek");
        }

        if ($price < 0) {
            throw new InvalidArgumentException("Price cannot be negative.");
        }

        $sql = "UPDATE subscription_plan 
            SET name = ?, duration = ?, price = ?, type_plan = ?, features = ?, description = ?
            WHERE sub_id = ?";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param(
            "sidsssi",
            $name,
            $durationMonths,
            $price,
            $typePlan,
            $features,
            $description,
            $id
        );

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
    public function deletePlan(int $id): bool
    {
        $sql = "DELETE FROM subscription_plan WHERE sub_id = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param("i", $id);

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
    public function getPlans(int $id = null): array
    {
        // If ID is provided → return a single plan
        if ($id !== null) {
            $sql = "SELECT * FROM subscription_plan WHERE sub_id = ?";
            $stmt = $this->conn->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $result = $stmt->get_result();
            $plan = $result->fetch_assoc();

            $stmt->close();

            // Always return an array for consistency
            return $plan ? [$plan] : [];
        }

        // No ID → return all plans
        $sql = "SELECT * FROM subscription_plan ORDER BY sub_id DESC";
        $result = $this->conn->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception("Error retrieving plans: " . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function get_all_boards(int $id = null): array
    {
        // Base query: all boards or single board by ID
        if ($id === null) {
            $sql = "SELECT * FROM hardware ORDER BY id DESC";
            $stmt = $this->conn->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $boards = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $sql = "SELECT * FROM hardware WHERE id = ?";
            $stmt = $this->conn->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $boards = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        // Check pairing with users
        foreach ($boards as &$board) {
            $hardwareId = $board['id'];

            $sqlUser = "SELECT id_users, hardware_id,email
                    FROM users 
                    WHERE role IN ('Admin') AND hardware_id = ? 
                    ORDER BY id_users DESC 
                    LIMIT 1";

            $stmtUser = $this->conn->prepare($sqlUser);

            if ($stmtUser === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmtUser->bind_param("i", $hardwareId);
            $stmtUser->execute();
            $resultUser = $stmtUser->get_result();

            // If a user exists for this hardware → paired = true
            $board['paired'] = $resultUser->num_rows > 0;
            $user = $resultUser->fetch_assoc();
            $board['paired_email'] = $user ? $user['email'] : null;
            $board['paired_user_id'] = $user ? $user['id_users'] : null;

            $stmtUser->close();
        }

        return $boards;
    }
    public function register_board(array $data): int
    {
        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new InvalidArgumentException("Board name is required.");
        }

        $name = trim($data['name']);
        $location = isset($data['location']) ? trim($data['location']) : null;
         

        $sql = "INSERT INTO hardware (name,location,passkey) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param("sss", $name, $location, $password);

        if (!$stmt->execute()) {
            throw new mysqli_sql_exception("Error registering board: " . $stmt->error);
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }
    public function update_board(int $id, array $data): bool
    {
        if (!isset($data['name']) || trim($data['name']) === '') {
            throw new InvalidArgumentException("Board name is required." . " " . json_encode($data));
        }

        $name     = trim($data['name']);
        $location = isset($data['location']) ? trim($data['location']) : null;
        $password = isset($data['password']) ? hash('sha256', $data['password']) : null;

        // Build SQL dynamically depending on whether password is provided
        if ($password) {
            $sql = "UPDATE hardware SET name = ?, location = ?, passkey = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("sssi", $name, $location, $password, $id);
        } else {
            $sql = "UPDATE hardware SET name = ?, location = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("ssi", $name, $location, $id);
        }

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
    public function delete_board(int $id): bool
    {
        $sql = "DELETE FROM hardware WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param("i", $id);

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
    public function get_unpaired_users(): array
    {
        $sql = "SELECT id_users, email, role, status
            FROM users
            WHERE hardware_id IS NULL AND role IN ('Admin')
            ORDER BY id_users DESC";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception("Error retrieving unpaired users: " . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function pair_user_to_board(int $userId, int $boardId): bool
    {
        // Check if board exists
        $stmtBoard = $this->conn->prepare("SELECT id FROM hardware WHERE id = ?");
        $stmtBoard->bind_param("i", $boardId);
        $stmtBoard->execute();
        $resultBoard = $stmtBoard->get_result();

        if ($resultBoard->num_rows === 0) {
            $stmtBoard->close();
            throw new Exception("Board with ID {$boardId} does not exist.");
        }
        $stmtBoard->close();

        // Check if user exists and is an Admin
        $stmtUser = $this->conn->prepare("SELECT id_users FROM users WHERE id_users = ? AND role IN ('Admin')");
        $stmtUser->bind_param("i", $userId);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows === 0) {
            $stmtUser->close();
            throw new Exception("Admin user with ID {$userId} does not exist.");
        }
        $stmtUser->close();

        // Pair user to board
        $sql = "UPDATE users SET hardware_id = ? WHERE id_users = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param("ii", $boardId, $userId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
    public function unpair_user_from_board($userId, $boardId): bool
    {
        // Check if user exists and is an Admin
        $stmtUser = $this->conn->prepare("SELECT id_users FROM users WHERE id_users = ? AND hardware_id=? AND role IN ('Admin')");
        $stmtUser->bind_param("ii", $userId, $boardId);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($resultUser->num_rows === 0) {
            $stmtUser->close();
            throw new Exception("Admin user with ID {$userId} is not paired to board with ID {$boardId}.");
        }
        $stmtUser->close();

        // Unpair user from board
        $sql = "UPDATE users SET hardware_id = NULL WHERE id_users = ?";
        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
    public function get_users_with_subscriptions(int $userId = null): array
    {
        $sql = "SELECT 
    u.id_users AS user_id,
    u.email,
    u.status,
    u.hardware_id,
    s.date_of_expiry,
    sp.name AS subscription_name
FROM users u
LEFT JOIN subscriptions s ON u.id_users = s.id
LEFT JOIN subscription_plan sp ON s.sub_id = sp.sub_id
WHERE u.role IN ('Admin')";

        // If a specific user ID is provided → filter
        if ($userId !== null) {
            $sql .= " AND u.id_users = ?";
            $stmt = $this->conn->prepare($sql);

            if ($stmt === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            return $data ? [$data] : [];
        }

        // Otherwise → fetch all users
        $sql .= " ORDER BY u.id_users DESC";
        $result = $this->conn->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception("Error retrieving users: " . $this->conn->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateSubscription_user(int $userId, int $planType): bool
    {
        // Step 1: Get plan duration
        $sqlPlan = "SELECT duration FROM subscription_plan WHERE sub_id = ?";
        $stmtPlan = $this->conn->prepare($sqlPlan);
        if ($stmtPlan === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmtPlan->bind_param("i", $planType);
        $stmtPlan->execute();
        $resultPlan = $stmtPlan->get_result();
        $plan = $resultPlan->fetch_assoc();
        $stmtPlan->close();

        if (!$plan) {
            throw new InvalidArgumentException("Invalid plan type provided.");
        }

        $duration = (int)$plan['duration'];
        $expiryDate = (new DateTime())->add(new DateInterval("P{$duration}M"))->format("Y-m-d H:i:s");

        // Step 2: Check if user already has a subscription
        $sqlCheck = "SELECT id FROM subscriptions WHERE id = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        if ($stmtCheck === false) {
            throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
        }

        $stmtCheck->bind_param("i", $userId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $existing = $resultCheck->fetch_assoc();
        $stmtCheck->close();

        if ($existing) {
            // Step 3: Update existing subscription
            $sqlUpdate = "UPDATE subscriptions 
                      SET date_of_expiry = ?, sub_id = ?, status = 1 
                      WHERE id = ?";
            $stmtUpdate = $this->conn->prepare($sqlUpdate);
            if ($stmtUpdate === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmtUpdate->bind_param("sii", $expiryDate, $planType, $userId);
            $success = $stmtUpdate->execute();
            $stmtUpdate->close();

            if ($success) {
                // Step 4: Record in revenues
                $sqlRevenue = "INSERT INTO revenues (sub_id, user_id) VALUES (?, ?)";
                $stmtRevenue = $this->conn->prepare($sqlRevenue);
                if ($stmtRevenue === false) {
                    throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
                }
                $stmtRevenue->bind_param("ii", $planType, $userId);
                $stmtRevenue->execute();
                $stmtRevenue->close();
            }

            return $success;
        } else {
            // Step 5: Insert new subscription
            $sqlInsert = "INSERT INTO subscriptions (id, date_of_expiry, status, sub_id) 
                      VALUES (?, ?, 1, ?)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            if ($stmtInsert === false) {
                throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
            }

            $stmtInsert->bind_param("isi", $userId, $expiryDate, $planType);
            $success = $stmtInsert->execute();
            $stmtInsert->close();

            if ($success) {
                // Step 6: Record in revenues
                $sqlRevenue = "INSERT INTO revenues (sub_id, user_id) VALUES (?, ?)";
                $stmtRevenue = $this->conn->prepare($sqlRevenue);
                if ($stmtRevenue === false) {
                    throw new mysqli_sql_exception("Failed to prepare statement: " . $this->conn->error);
                }
                $stmtRevenue->bind_param("ii", $planType, $userId);
                $stmtRevenue->execute();
                $stmtRevenue->close();
            }

            return $success;
        }
    }

    public function get_subscription_chart_data(): array
    {
        // Active (paid, not expired)
        $sqlActive = "SELECT COUNT(*) AS total
                  FROM subscriptions s
                  INNER JOIN subscription_plan sp ON s.sub_id = sp.sub_id
                  WHERE sp.price > 0 AND s.date_of_expiry >= NOW()";
        $active = $this->conn->query($sqlActive)->fetch_assoc()['total'];

        // Trial (free tier)
        $sqlTrial = "SELECT COUNT(*) AS total
                 FROM subscriptions s
                 INNER JOIN subscription_plan sp ON s.sub_id = sp.sub_id
                 WHERE sp.price = 0";
        $trial = $this->conn->query($sqlTrial)->fetch_assoc()['total'];

        // Expired
        $sqlExpired = "SELECT COUNT(*) AS total
                   FROM subscriptions s
                   WHERE s.date_of_expiry < NOW()";
        $expired = $this->conn->query($sqlExpired)->fetch_assoc()['total'];

        // Unprepared (no subscription row)
        $sqlUnprepared = "SELECT COUNT(*) AS total
                      FROM users u
                      LEFT JOIN subscriptions s ON u.id_users = s.id
                      WHERE s.id IS NULL AND u.role ='Admin'";
        $unprepared = $this->conn->query($sqlUnprepared)->fetch_assoc()['total'];

        return [
            "labels" => ["Active", "Trial", "Expired", "Unsubscribed"],
            "data" => [$active, $trial, $expired, $unprepared],
            "backgroundColor" => [
                "#fde047", // brighter
                "#facc15", // deep golden shade
                "#eab308", // deeper amber
                "#ca8a04"  // dark mustard
            ]
        ];
    }
    public function get_admin_new_users_by_month(): array
    {
        $sql = "SELECT 
                DATE_FORMAT(created_at, '%b %Y') AS month_label,
                COUNT(*) AS total
            FROM users
            WHERE role = 'Admin'
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception("Error retrieving monthly new users: " . $this->conn->error);
        }

        $labels = [];
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['month_label'];   // e.g. "Jan 2026"
            $data[]   = (int)$row['total'];    // number of Admins joined that month
        }

        return [
            "labels" => $labels,
            "data" => $data,
            "backgroundColor" => [
                "#fce24e", // base yellow
                "#fcd34d", // slightly darker
                "#fde047", // brighter
                "#facc15", // golden shade
                "#eab308", // deeper amber
                "#ca8a04"  // dark mustard
            ]
        ];
    }
    public function get_monthly_revenues(): array
    {
        $sql = "SELECT 
                DATE_FORMAT(r.date_of_purchase, '%b %Y') AS month_label,
                SUM(sp.price) AS total_revenue
            FROM revenues r
            INNER JOIN subscription_plan sp ON r.sub_id = sp.sub_id
            GROUP BY YEAR(r.date_of_purchase), MONTH(r.date_of_purchase)
            ORDER BY YEAR(r.date_of_purchase), MONTH(r.date_of_purchase)";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception("Error retrieving monthly revenues: " . $this->conn->error);
        }

        $labels = [];
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $labels[] = $row['month_label'];         // e.g. "Jan 2026"
            $data[]   = (int)$row['total_revenue'];  // sum of subscription prices that month
        }

        return [
            "labels" => $labels,
            "data" => $data
        ];
    }

    public function get_monthly_retention(): array
    {
        /**
         * Retention formula:
         * (Users at End of Period - New Users Acquired) / Users at Start of Period * 100
         *
         * We'll calculate this month by month using the users table.
         */

        $sql = "SELECT 
                YEAR(created_at) AS yr,
                MONTH(created_at) AS mn,
                DATE_FORMAT(created_at, '%b %Y') AS month_label,
                COUNT(*) AS new_users
            FROM users
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY YEAR(created_at), MONTH(created_at)";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new mysqli_sql_exception("Error retrieving monthly users: " . $this->conn->error);
        }

        $labels = [];
        $data   = [];

        $usersAtStart = 0;
        $usersAtEnd   = 0;

        while ($row = $result->fetch_assoc()) {
            $newUsers = (int)$row['new_users'];

            // Users at start = previous end
            $usersAtStart = $usersAtEnd > 0 ? $usersAtEnd : $newUsers;

            // Users at end = start + new users
            $usersAtEnd = $usersAtStart + $newUsers;

            // Retention rate formula
            $retentionRate = (($usersAtEnd - $newUsers) / $usersAtStart) * 100;

            $labels[] = $row['month_label'];          // e.g. "Jan 2026"
            $data[]   = round($retentionRate);        // rounded percentage
        }

        return [
            "labels" => $labels,
            "data" => $data
        ];
    }
}
