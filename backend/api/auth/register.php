<?php
require_once("../../config/database.php");

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed"
    ]);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Name, email, and password are required"
    ]);
    exit();
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];
$confirmPassword = $data['confirmPassword'] ?? '';
// Role yang diminta (jika tidak ada, default consumer)
$role = $data['role'] ?? 'consumer';

// Validation
if (empty($name) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required"
    ]);
    exit();
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Password must be at least 6 characters"
    ]);
    exit();
}

if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Password and confirm password do not match"
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email format"
    ]);
    exit();
}

// Check if email already exists
$checkEmail = "SELECT id FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $checkEmail);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    http_response_code(409);
    echo json_encode([
        "status" => "error",
        "message" => "Email already registered. Please use another email or login."
    ]);
    mysqli_stmt_close($stmt);
    exit();
}
mysqli_stmt_close($stmt);

// Normalisasi & validasi role (hanya boleh consumer atau umkm dari endpoint publik)
$allowedRoles = ['consumer', 'umkm'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'consumer';
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$insertUser = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $insertUser);
mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashedPassword, $role);

if (mysqli_stmt_execute($stmt)) {
    $userId = mysqli_insert_id($conn);
    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful",
        "user" => [
            "id" => $userId,
            "name" => $name,
            "email" => $email,
            "role" => $role
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Registration failed. Please try again."
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

