<?php
// CORS for Swagger/UI
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
/**
 * API Endpoint: Create Error Log
 * 
 * Description: Creates a new error log entry
 * Method: POST
 * URL: /api/v1/error_logs/create_log.php
 * 
 * Headers:
 * - Content-Type: application/json
 * 
 * Request Body:
 * {
 *     "user_id": "1",
 *     "organization_id": "xyz",
 *     "product_id": "1",
 *     "STATUS": "error",
 *     "MESSAGE": "Validation failed",
 *     "CODE": "422",
 *     "DATA": {
 *         "validation_errors": {
 *             "email_address": "Email format is invalid",
 *             "password": "Password must be at least 8 characters"
 *         }
 *     },
 *     "TIMESTAMP": "2024-01-15 14:25:30"
 * }
 * 
 * Success Response (201):
 * {
 *     "STATUS": "success",
 *     "MESSAGE": "Error log created successfully",
 *     "CODE": "201",
 *     "DATA": {
 *         "log_uuid": "3f42a5b2-9e7f-4ac0-8c8b-7f2f7f3a2c1d",
 *         "created_at": "2024-01-15 14:25:30"
 *     },
 *     "TIMESTAMP": "2024-01-15 14:25:30"
 * }
 * 
 * Error Responses:
 * - 400: Bad Request (Invalid input data)
 * - 422: Validation Error (Field validation failed)
 * - 500: Internal Server Error
 */

// Include required files
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/response_helpers.php';
require_once __DIR__ . '/../../../includes/validation_helpers.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error_response('Method not allowed', 405);
}

try {
    // Get JSON input
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true);
    
    // Check if JSON is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_error_response('Invalid JSON format', 400);
    }
    
    // Validate error log data
    $validation_errors = validate_error_log_data($input_data);
    
    // Return validation errors if any
    if (!empty($validation_errors)) {
        send_validation_error_response($validation_errors);
    }
    
    // Create error log in database
    $log_id = create_error_log($input_data);
    
    if ($log_id === false) {
        send_error_response('Failed to create error log', 500);
    }
    
    // Get created log data
    $log_data = get_error_log_by_id($log_id);
    
    if ($log_data === false) {
        send_error_response('Error log created but could not retrieve data', 500);
    }
    
    // Prepare response data
    $response_data = [
        'log_id' => $log_id,
        'created_at' => $log_data['created_at']
    ];
    
    // Send success response
    send_success_response('Error log created successfully', $response_data, 201);
    
} catch (Exception $e) {
    // Log error and send generic error response
    error_log("Error in create_log.php: " . $e->getMessage());
    send_error_response('An unexpected error occurred', 500);
}
?> 