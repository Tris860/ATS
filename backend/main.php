<?php

// main.php (your actual backend API endpoint)

// IMPORTANT: ALL ini_set calls related to session configuration MUST be BEFORE session_start().
// There must be NO output (not even a space or newline) before session_start().
ini_set('session.cookie_httponly', 1);
// Uncomment the line below ONLY if your frontend is served over HTTPS!
// If your frontend is HTTP, uncommenting this will prevent sessions from working.
// ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_trans_sid', 0);

// IMPORTANT: session_start() MUST be the very first executable statement
// after ini_set calls. No spaces, no newlines, no HTML before this.
session_start();

// --- SESSION HEALTH CHECK (TEMPORARY DEBUGGING BLOCK) ---
// This block will help us confirm if sessions are working at all.
if (!isset($_SESSION['session_test_key'])) {
    $_SESSION['session_test_key'] = 'Session is working!';
    error_log("DEBUG-SESSION: Session test key set.");
} else {
    error_log("DEBUG-SESSION: Session test key found: " . $_SESSION['session_test_key']);
}
// --- END SESSION HEALTH CHECK ---


// IMPORTANT: In production, remove or comment out these lines!
// They are for development debugging only and can expose sensitive information.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // Keep this for now to see other potential errors

// Allow cross-origin requests (for development; restrict in production)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // IMPORTANT: Change * to your frontend's domain in production!
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request (important for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include manager.php which is assumed to contain User, TimetableManager, and Commander classes
require_once 'manager.php'; 

// Database connection details
$servername = "localhost";
$username = "root";
$password = ""; // Your actual password
$dbname = "atsfinal"; // Your actual database name

$conn = null; // Initialize connection variable
$response = ["success" => false, "message" => "An unknown error occurred."]; // Default error response

// Get user email from session and login status after session_start()
$loggedInUserEmail = $_SESSION['email'] ?? null;
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$loggedInUserId = $_SESSION['user_id'] ?? null; // Get user ID from session

try {
    // Establish database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Instantiate all necessary managers
    $userManager = new User($conn);
    $timeChecker = new TimeChecker($conn);
    $timetableManager = new TimetableManager($conn);
    $commander = new Commander($conn); // Instantiate Commander as it's part of manager.php

    $action = ''; // Initialize action to empty
    $requestData = []; // This will hold parsed request body data

    // For POST requests, parse input based on Content-Type
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]);

        // Log raw input for debugging POST requests
        $rawInput = file_get_contents('php://input');
        error_log("DEBUG-API: Raw POST input: " . $rawInput);
        error_log("DEBUG-API: Content-Type: " . ($contentType ?: 'N/A'));

        if ($contentType === 'application/json') {
            $requestData = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException("Invalid JSON input: " . json_last_error_msg());
            }
        } else if ($contentType === 'application/x-www-form-urlencoded' || $contentType === 'multipart/form-data') {
            // For form data (like login/register), use $_POST
            $requestData = $_POST;
            // Log $_POST content for debugging
            error_log("DEBUG-API: \$_POST content: " . print_r($_POST, true));
        }
        // Prioritize action from requestData (POST body)
        $action = $requestData['action'] ?? '';
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // For GET requests, action comes from $_GET
        $action = $_GET['action'] ?? '';
    }

    // --- DEBUGGING: Log received action and session state ---
    error_log("DEBUG-API: Final determined action: " . $action);
    error_log("DEBUG-API: Request Data (parsed): " . print_r($requestData, true)); // Log parsed request data
    error_log("DEBUG-API: Session status: " . session_status());
    error_log("DEBUG-API: _SESSION content: " . print_r($_SESSION, true)); // This will show session content
    // --- END DEBUGGING ---

    // Handle different actions
    switch ($action) {
        // --- User/Authentication Actions ---
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $requestData['email'] ?? '';
                $passkey = $requestData['password'] ?? '';
                $response = $userManager->register($email, $passkey);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for register. Use POST."];
            }
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $requestData['email'] ?? '';
                $passkey = $requestData['password'] ?? '';
                $response = $userManager->login($email, $passkey); // This sets session variables on success
                // After successful login, refresh the $loggedInUserEmail and $isLoggedIn variables
                $loggedInUserEmail = $_SESSION['email'] ?? null;
                $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
                $loggedInUserId = $_SESSION['user_id'] ?? null; // Refresh user ID
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for login. Use POST."];
            }
        break;
        case 'logout':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $response = $userManager->logout(); // This destroys session
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for logout. Use POST."];
            }
            break;

        case 'get_user_email':
            // This endpoint now reports email, timetable_enabled, and hard_switch_enabled status
            $userEmail = $loggedInUserEmail ?? 'Guest User';
            $timetableEnabled = $_SESSION['timetable_enabled'] ?? true; // Default to true if not set
            $hardSwitchEnabled = $_SESSION['hard_switch_enabled'] ?? true; // Default to true if not set
            
            // If not logged in, ensure default values are returned
            if (!$isLoggedIn) {
                $userEmail = 'Guest User';
                $timetableEnabled = true; 
                $hardSwitchEnabled = true; 
            }
            $response = ["success" => true, "email" => $userEmail, "timetable_enabled" => $timetableEnabled, "hard_switch_enabled" => $hardSwitchEnabled];
            break;

        case 'change_password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ensure user is logged in
                if (!$isLoggedIn || !$loggedInUserId) { // Use loggedInUserId for authentication check
                    $response = ["success" => false, "message" => "Not logged in to change password."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                $currentPassword = $requestData['current_password'] ?? '';
                $newPassword = $requestData['new_password'] ?? '';
                // Call userManager method with userId
                $response = $userManager->changePassword($loggedInUserId, $currentPassword, $newPassword);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for change password. Use POST."];
            }
            break;

        case 'change_email':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ensure user is logged in
                if (!$isLoggedIn || !$loggedInUserId) { // Use loggedInUserId for authentication check
                    $response = ["success" => false, "message" => "Not logged in to change email."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                $currentPassword = $requestData['current_password'] ?? '';
                $newEmail = $requestData['new_email'] ?? '';
                // Call userManager method with userId
                $response = $userManager->changeEmail($loggedInUserId, $currentPassword, $newEmail);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for change email. Use POST."];
            }
            break;
        
        case 'toggle_timetable_visibility':
            error_log("DEBUG-API: Handling toggle_timetable_visibility action.");
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$isLoggedIn || !$loggedInUserId) {
                    error_log("DEBUG-API: User not logged in for toggle_timetable_visibility. Session User ID: " . ($loggedInUserId ?? 'NULL'));
                    $response = ["success" => false, "message" => "Not logged in to change timetable visibility."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                $enabled = (bool)($requestData['enabled'] ?? true); // Default to true if not explicitly false
                error_log("DEBUG-API: toggle_timetable_visibility - User ID: {$loggedInUserId}, Enabled: " . ($enabled ? 'true' : 'false'));
                // Call userManager method with userId
                $response = $userManager->toggleTimetableVisibility($loggedInUserId, $enabled);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for toggle timetable visibility. Use POST."];
            }
            break;

        case 'toggle_hard_switch':
            error_log("DEBUG-API: Handling toggle_hard_switch action.");
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$isLoggedIn || !$loggedInUserId) {
                    error_log("DEBUG-API: User not logged in for toggle_hard_switch. Session User ID: " . ($loggedInUserId ?? 'NULL'));
                    $response = ["success" => false, "message" => "Not logged in to change hard switch status."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                $enabled = (bool)($requestData['enabled'] ?? true); // Default to true if not explicitly false
                error_log("DEBUG-API: toggle_hard_switch - User ID: {$loggedInUserId}, Enabled: " . ($enabled ? 'true' : 'false'));
                // Call userManager method with userId
                $response = $userManager->toggleHardSwitch($loggedInUserId, $enabled);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for toggle hard switch. Use POST."];
            }
            break;

        case 'ring_bell':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$isLoggedIn) {
                    $response = ["success" => false, "message" => "Not logged in to ring the bell."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                // Retrieve hard switch status from session
                $hardSwitchStatus = $_SESSION['hard_switch_enabled'] ?? true; 

                if (!$hardSwitchStatus) {
                    $response = ["success" => false, "message" => "Bell cannot be rung: Hard switch is OFF."];
                } else {
                    // In a real application, this is where you'd trigger the actual bell mechanism.
                    // For this example, we just return a success message.
                    $response = ["success" => true, "message" => "Bell rung successfully!"];
                }
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for ring bell. Use POST."];
            }
            break;

        // --- Timetable Actions ---
        case 'get_all':
            // This is a GET request, so get 'day' from $_GET
            $dayFilter = $_GET['day'] ?? null;
            // In a real app, you'd pass $loggedInUserId here to filter periods by user ownership
            $periods = $timetableManager->getAllPeriods($dayFilter, $loggedInUserEmail ?? '');
            $response = ["success" => true, "periods" => $periods];
            break;

        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Ensure user is logged in to add periods
                if (!$isLoggedIn || !$loggedInUserId) {
                    $response = ["success" => false, "message" => "Not logged in to add periods."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                // Data comes from JSON input for POST requests, now in $requestData
                $data = $requestData;
                // Add user_id to the data before passing to TimetableManager
                $data['user_id'] = $loggedInUserId; 
                $insertId = $timetableManager->addPeriod($data, $loggedInUserEmail ?? '');
                $response = ["success" => true, "message" => "Period added successfully with ID: " . $insertId];
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for add. Use POST."];
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Frontend uses POST for updates
                // Ensure user is logged in to update periods
                if (!$isLoggedIn || !$loggedInUserId) {
                    $response = ["success" => false, "message" => "Not logged in to update periods."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                // Data comes from JSON input for POST requests, now in $requestData
                $data = $requestData;
                $id = (int)($data['id'] ?? 0); // Get ID from the request body
                
                if ($id <= 0) {
                    throw new InvalidArgumentException("Invalid ID provided for update.");
                }

                // Add user_id to the data for ownership verification in TimetableManager
                $data['user_id'] = $loggedInUserId; 
                $updated = $timetableManager->updatePeriod($id, $data);
                if ($updated) {
                    $response = ["success" => true, "message" => "Period updated successfully."];
                } else {
                    $response = ["success" => false, "message" => "No changes made or period not found."];
                }
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for update. Use POST."];
            }
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Frontend uses POST for deletes
                // Ensure user is logged in to delete periods
                if (!$isLoggedIn || !$loggedInUserId) {
                    $response = ["success" => false, "message" => "Not logged in to delete periods."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                // For DELETE, ID can come from query string or JSON body.
                // Assuming query string for simplicity based on frontend fetch.
                $id = (int)($_GET['id'] ?? ($requestData['id'] ?? 0)); // Use $requestData for POST body
                if ($id <= 0) {
                    throw new InvalidArgumentException("Invalid ID provided for delete.");
                }
                // Add user_id to the data for ownership verification in TimetableManager
                $data['user_id'] = $loggedInUserId; 
                $deleted = $timetableManager->deletePeriod($id);
                if ($deleted) {
                    $response = ["success" => true, "message" => "Period deleted successfully."];
                } else {
                    $response = ["success" => false, "message" => "Period not found for deletion."];
                }
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for delete. Use POST."];
            }
            break;
        case 'is_current_time_in_period':
         if ($_SERVER['REQUEST_METHOD'] === 'GET') {
               $response = $timeChecker->checkCurrentTimeInPeriod();
                // $response = ["success" => true, "message" => "SHIMO method for change email. Use POST."];
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for change email. Use POST."];
          }
        break;
        case 'save_timezone':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!$isLoggedIn || !$loggedInUserId) {
                    $response = ["success" => false, "message" => "Not logged in to save timezone."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                $timezone = $requestData['timezone'] ?? '';
                if (empty($timezone)) {
                    throw new InvalidArgumentException("Timezone is required.");
                }
                $response = $userManager->saveTimezone($loggedInUserId, $timezone);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for save timezone. Use POST."];
            }
        break;
        case 'get_timezone':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                if (!$isLoggedIn || !$loggedInUserId) {
                    $response = ["success" => false, "message" => "Not logged in to get timezone."];
                    http_response_code(401); // Unauthorized
                    break;
                }
                $response = $userManager->getTimezone($loggedInUserId);
            } else {
                http_response_code(405);
                $response = ["success" => false, "message" => "Invalid request method for get timezone. Use GET."];
            }
        break;
        case 'wemos_auth':
            if($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? null;
                $password = $_POST['password'] ?? null;
                $response =$userManager->WEMOS_AUTH($username, $password);
                //  $response= ["success" => false, "message" => $_POST['password'].' Authentication data (username and password) is missing.'];
            } else {
                http_response_code(400); // Bad Request
                $response= ["success" => false, "message" => 'Authentication data (username and password) is missing.'];
            }
        break;
        case 'get_user_device':
          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $email = $_POST['email'] ?? null;
              $devicename = $userManager->getAssignedDevice($email); // Custom method
              $response = $devicename ? ["success" => true, "device_name" => $devicename] : ["success" => false, "message" => "No device assigned."];
            } else {
               http_response_code(405);
               $response = ["success" => false, "message" => "Invalid request method for get_user_device. Use POST."];
            }
        break;


    case 'get_all_users':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!$isLoggedIn || !$loggedInUserId) {
                $response = ["success" => false, "message" => "Not logged in to fetch users."];
                http_response_code(401);
                break;
            }
            
            $response = $timeChecker->getAllUsers($loggedInUserId);
        } else {
            http_response_code(405);
            $response = ["success" => false, "message" => "Invalid request method for get_all_users. Use GET."];
        }
        break;
  
case 'update_subscription':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$isLoggedIn || !$loggedInUserId) {
            $response = ["success" => false, "message" => "Not logged in to update subscription."];
            http_response_code(401);
            break;
        }

        $planType = $_POST['plan_type'] ?? null;
        $targetUserId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null; // ✅ cast to int

        if (!$planType || !$targetUserId) {
            $response = ["success" => false, "message" => "Missing parameters (plan_type or user_id)."];
            http_response_code(400);
            break;
        }

        // ✅ update subscription for the client, not the admin
        $response = $timeChecker->updateSubscription($targetUserId, $planType);

    } else {
        http_response_code(405);
        $response = ["success" => false, "message" => "Invalid request method for update_subscription. Use POST."];
    }
    break;

case 'update_password':
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$isLoggedIn || !$loggedInUserId) {
            $response = ["success" => false, "message" => "Not logged in to update password."];
            http_response_code(401);
            break;
        }

        $newPassword = $_POST['new_password'] ?? null;
        $targetUserId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null; // ✅ cast to int

        if (!$newPassword || !$targetUserId) {
            $response = ["success" => false, "message" => "Missing parameters (new_password or user_id)."];
            http_response_code(400);
            break;
        }

        // ✅ update password for the client, not the admin
        $response = $timeChecker->updateUserPassword($targetUserId, $newPassword);

    } else {
        http_response_code(405);
        $response = ["success" => false, "message" => "Invalid request method for update_password. Use POST."];
    }
    break;



    case 'get_users_by_status':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!$isLoggedIn || !$loggedInUserId) {
                $response = ["success" => false, "message" => "Not logged in to fetch users by status."];
                http_response_code(401);
                break;
            }
            $status = $_GET['status'] ?? null;
            if ($status === null) {
                $response = ["success" => false, "message" => "Missing status parameter."];
                http_response_code(400);
                break;
            }
            $response = $timeChecker->getUsersWithSubscriptionByStatus((int)$status);
        } else {
            http_response_code(405);
            $response = ["success" => false, "message" => "Invalid request method for get_users_by_status. Use GET."];
        }
        break;


        default:
            http_response_code(400); // Bad Request
            $response = ["success" => false, "message" => "Unknown action: " . ($action === '' ? '[empty]' : $action)];
            break;
    }

} catch (InvalidArgumentException $e) {
    http_response_code(400); // Bad Request for client input errors
    $response = ["success" => false, "message" => $e->getMessage()];
} catch (mysqli_sql_exception $e) {
    http_response_code(500); // Internal Server Error for database issues
    error_log("Database Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $response = ["success" => false, "message" => "A database error occurred.". $e->getMessage()." Please try again later."];
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error for any other unexpected errors
    error_log("General Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $response = ["success" => false, "message" => "An unexpected server error occurred. Please try again."];
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);

?>
