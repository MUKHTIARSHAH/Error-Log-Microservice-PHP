<?php
/**
 * Standardized API response functions
 * All API endpoints must use these functions for consistent responses
 */

/**
 * Send success response
 * @param string $message Success message
 * @param mixed $data Response data
 * @param int $code HTTP status code (default: 200)
 */
function send_success_response($message, $data = [], $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    $response = [
        'STATUS' => 'success',
        'MESSAGE' => $message,
        'CODE' => strval($code),
        'DATA' => $data,
        'TIMESTAMP' => date('Y-m-d H:i:s')
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send error response
 * @param string $message Error message
 * @param int $code HTTP status code (default: 400)
 * @param mixed $data Additional error data
 */
function send_error_response($message, $code = 400, $data = []) {
    http_response_code($code);
    header('Content-Type: application/json');
    $response = [
        'STATUS' => 'error',
        'MESSAGE' => $message,
        'CODE' => strval($code),
        'DATA' => $data,
        'TIMESTAMP' => date('Y-m-d H:i:s')
    ];
    error_log("API Error: " . $message . " (Code: " . $code . ")");
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send validation error response
 * @param array $validation_errors Array of validation errors
 */
function send_validation_error_response($validation_errors) {
    send_error_response('Validation failed', 422, [
        'validation_errors' => $validation_errors
    ]);
}
?>