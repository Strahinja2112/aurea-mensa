<?php
declare(strict_types=1);

// Prevent any accidental output before headers
ob_start();

require_once BASE_PATH . '/server/Database.php';
require_once BASE_PATH . '/server/Auth.php';
require_once BASE_PATH . '/server/Mailer.php';

header('Content-Type: application/json');

try {
    $inputData = json_decode(file_get_contents('php://input'), true);

    if (!$inputData) {
        throw new Exception("Invalid JSON input.");
    }

    // Call the Auth method (ensure it returns the user array)
    $newUser = Auth::registerNewUser($inputData);

    // Clear buffer to ensure only JSON is sent
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'User registered successfully. Please check your email.',
        'user' => $newUser
    ]);

} catch (Throwable $exception) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage()
    ]);
}

exit;