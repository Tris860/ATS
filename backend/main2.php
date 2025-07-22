<?php
// timetable_api.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Allow cross-origin requests (for development; restrict in production)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // IMPORTANT: Restrict this to your frontend's domain in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
 
// Handle preflight OPTIONS request (important for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include the TimetableManager class
require_once 'manager.php'; // Make sure this path is correct

// Database connection details
$servername = "localhost";
$username = "root";
$password = "shimo@123flex"; // Your actual password
$dbname = "atsfinal"; // Your actual database name

$conn = null; // Initialize connection variable
$response = ["success" => false, "message" => "An unknown error occurred."];



$timeout = 1800; // 30 minutes

try {

    // Establish database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        // This will be caught by the outer catch if TimetableManager constructor is called
        // but it's good to have a direct check for immediate connection failure
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Instantiate the TimetableManager
    $timetableManager = new TimetableManager($conn);
    $user=new User($conn);

    // if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
    //     session_unset();
    //     session_destroy();
    //     header("Location: index.html?timeout=true");
    //     exit;
    // }
    
    // Get the action from the query string
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mode = isset($_POST['mode']) ? $_POST['mode'] : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
    
        if ($mode === 'register'){
            $result = $user->register($email, $password);
            $response = $result;
        } elseif ($mode === 'login') {
            $result = $user->login($email, $password);
            $response = $result;
        } else {
            $response['message'] = 'Invalid mode specified.';
        }
    }elseif($_SERVER['REQUEST_METHOD'] === 'GET'){
        $action = $_GET['action'] ?? '';

    // Handle different actions
    switch ($action) {
        case 'get_all':
            $dayFilter = $_GET['day'] ?? null; // Get day filter from frontend
            $periods = $timetableManager->getAllPeriods($dayFilter);
            $response = ["success" => true, "periods" => $periods];
            break;

        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $insertId = $timetableManager->addPeriod($data);
                $response = ["success" => true, "message" => "Period added successfully with ID: " . $insertId];
            } else {
                http_response_code(405); // Method Not Allowed
                $response = ["success" => false, "message" => "Invalid request method for add. Use POST."];
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
                $id = (int)($_GET['id'] ?? 0);
                $data = json_decode(file_get_contents('php://input'), true);
                
                if ($id <= 0) {
                    throw new InvalidArgumentException("Invalid ID provided for update.");
                }

                $updated = $timetableManager->updatePeriod($id, $data);
                if ($updated) {
                    $response = ["success" => true, "message" => "Period updated successfully."];
                } else {
                    $response = ["success" => false, "message" => "No changes made or period not found."];
                }
            } else {
                http_response_code(405); // Method Not Allowed
                $response = ["success" => false, "message" => "Invalid request method for update. Use POST or PUT."];
            }
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $id = (int)($_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    throw new InvalidArgumentException("Invalid ID provided for delete.");
                }

                $deleted = $timetableManager->deletePeriod($id);
                if ($deleted) {
                    $response = ["success" => true, "message" => "Period deleted successfully."];
                } else {
                    // This case should ideally be caught by the exception in TimetableManager
                    $response = ["success" => false, "message" => "Period not found for deletion."];
                }
            } else {
                http_response_code(405); // Method Not Allowed
                $response = ["success" => false, "message" => "Invalid request method for delete. Use POST or DELETE."];
            }
            break;

            case 'get_user_email':
                // In a real app, this email would be set after a successful login
                // For demonstration, let's set a dummy email if not already set.
                if (isset($_SESSION['email']) AND session_status() === PHP_SESSION_NONE) {
                    $_SESSION['user_email'] = session_status(); // Dummy email for testing
                }
                $response = ["success" => true, "email" => $_SESSION['user_email']];
                break;
    
            // --- NEW: Endpoint to set a dummy user email in session (for testing) ---
            case 'set_dummy_email':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $_SESSION['email'] = $data['email'];
                        $response = ["success" => true, "message" => "Email set in session."];
                    } else {
                        throw new InvalidArgumentException("Invalid email provided.");
                    }
                } else {
                    http_response_code(405);
                    $response = ["success" => false, "message" => "Invalid request method for set_dummy_email. Use POST."];
                }
                break;
                case 'logout':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $response = $user->logout(); // This destroys session 
                    } else {
                        http_response_code(405);
                        $response = ["success" => false, "message" => "Invalid request method for logout. Use POST."];
                    }
                    break;
        
                case 'change_password':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // Ensure user is logged in and email is available in session
                        if (!$loggedInUserEmail || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                            $response = ["success" => false, "message" => "Not logged in to change password."];
                            http_response_code(401); // Unauthorized
                            break;
                        }
                        $data = json_decode(file_get_contents('php://input'), true);
                        $currentPassword = $data['current_password'] ?? '';
                        $newPassword = $data['new_password'] ?? '';
                        // Pass the logged-in user's email as the identifier
                        $response = $user->changePassword($loggedInUserEmail, $currentPassword, $newPassword);
                    } else {
                        http_response_code(405);
                        $response = ["success" => false, "message" => "Invalid request method for change password. Use POST."];
                    }
                    break;
        
                case 'change_email':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // Ensure user is logged in and email is available in session
                        if (!$loggedInUserEmail || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                            $response = ["success" => false, "message" => "Not logged in to change email."];
                            http_response_code(401); // Unauthorized
                            break;
                        }
                        $data = json_decode(file_get_contents('php://input'), true);
                        $currentPassword = $data['current_password'] ?? '';
                        $newEmail = $data['new_email'] ?? '';
                        // Pass the logged-in user's current email as the identifier
                        $response = $user->changeEmail($loggedInUserEmail, $currentPassword, $newEmail);
                    } else {
                        http_response_code(405);
                        $response = ["success" => false, "message" => "Invalid request method for change email. Use POST."];
                    }
                    break;
        
    
            default:
                http_response_code(400); // Bad Request
                $response = ["success" => false, "message" => "Unknown action: " . ($action === '' ? '[empty]' : $action)];
                break;
        }
    }
     else {
        $response['message'] = 'Invalid request method.';
    }

} catch (InvalidArgumentException $e) {
    http_response_code(400); // Bad Request for client input errors
    $response = ["success" => false, "message" => $e->getMessage()];
} catch (mysqli_sql_exception $e) {
    http_response_code(500); // Internal Server Error for database issues
    // Log the full error message for debugging, but send a generic one to client
    error_log("Database Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $response = ["success" => false, "message" => "A databases error occurred.".$e->getMessage()." Please try again later."];
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error for any other unexpected errors
    error_log("General Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    $response = ["success" => false, "message" => "An unexpected server error occurred. Please try again."];
} finally {
    // Ensure the database connection is closed
    if ($conn) {
        $conn->close();
    }
}
?>

