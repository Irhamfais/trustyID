<?php
// Set error reporting untuk development (nonaktifkan di production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error sebagai HTML

// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db   = "umkm_checker";

// Initialize connection variable
$conn = null;

// Create connection dengan error handling yang lebih baik
try {
    $conn = @mysqli_connect($host, $user, $pass, $db);
    
    // Check connection
    if (!$conn) {
        $errorMsg = mysqli_connect_error();
        if (empty($errorMsg)) {
            $errorMsg = "Cannot connect to MySQL server. Please ensure MySQL service is running in XAMPP.";
        }
        
        // Only output error if headers haven't been sent and this is a direct call
        // If included in another file, let that file handle the error
        if (!headers_sent() && basename($_SERVER['PHP_SELF']) === 'database.php') {
            header("Content-Type: application/json; charset=UTF-8");
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Database connection failed: " . $errorMsg,
                "hint" => "Please start MySQL service in XAMPP Control Panel"
            ], JSON_UNESCAPED_UNICODE);
        }
        // Don't exit here - let the calling file handle it
        $conn = false;
    } else {
        // Set charset to utf8
        mysqli_set_charset($conn, "utf8");
    }
} catch (Exception $e) {
    // Only output error if headers haven't been sent and this is a direct call
    if (!headers_sent() && basename($_SERVER['PHP_SELF']) === 'database.php') {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Database connection error: " . $e->getMessage(),
            "hint" => "Please start MySQL service in XAMPP Control Panel"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conn = false;
}
?>
