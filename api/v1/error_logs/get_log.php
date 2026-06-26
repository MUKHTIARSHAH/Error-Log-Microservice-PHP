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
 * API Endpoint: Get Error Log by UUID
 * 
 * Description: Retrieves a specific error log by its UUID
 * Method: GET
 * URL: /api/v1/error_logs/get_log.php?uuid={log_uuid}
 * 
 * Query Parameters:
 * - uuid: Error log UUID (required)
 * 
 * Success Response (200):
 * {
 *     "STATUS": "success",
 *     "MESSAGE": "Error log retrieved successfully",
 *     "CODE": "200",
 *     "DATA": {
 *         "uuid": "3f42a5b2-9e7f-4ac0-8c8b-7f2f7f3a2c1d",
 *         "user_id": 1,
 *         "organization_id": "xyz",
 *         "product_id": 1,
 *         "status": "error",
 *         "message": "Validation failed",
 *         "code": 422,
 *         "data": {
 *             "validation_errors": {
 *                 "email_address": "Email format is invalid",
 *                 "password": "Password must be at least 8 characters"
 *             }
 *         },
 *         "timestamp": "2024-01-15 14:25:30",
 *         "created_at": "2024-01-15 14:25:30"
 *     },
 *     "TIMESTAMP": "2024-01-15 14:25:30"
 * }
 * 
 * Error Responses:
 * - 400: Bad Request (Missing or invalid ID)
 * - 404: Not Found (Error log not found)
 * - 500: Internal Server Error
 */

// Include required files
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/response_helpers.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error_response('Method not allowed', 405);
}

try {
    // Get log UUID from query parameters
    $log_uuid = isset($_GET['uuid']) ? $_GET['uuid'] : '';
    
    // Validate log UUID
    if (empty($log_uuid) || !preg_match('/^[0-9a-fA-F-]{36}$/', $log_uuid)) {
        send_error_response('Valid error log UUID is required', 400);
    }
    
    // Get error log by UUID
    $log_data = get_error_log_by_uuid($log_uuid);
    
    if ($log_data === false) {
        send_error_response('Error log not found', 404);
    }
    
    // Send success response
    send_success_response('Error log retrieved successfully', $log_data);
    
} catch (Exception $e) {
    // Log error and send generic error response
    error_log("Error in get_log.php: " . $e->getMessage());
    send_error_response('An unexpected error occurred', 500);
}
?> 