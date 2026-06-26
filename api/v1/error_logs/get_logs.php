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
 * API Endpoint: Get Error Logs
 * 
 * Description: Retrieves error logs with pagination and filtering
 * Method: GET
 * URL: /api/v1/error_logs/get_logs.php
 * 
 * Query Parameters:
 * - page: Page number (default: 1)
 * - limit: Items per page (default: 10, max: 100)
 * - user_id: Filter by user ID
 * - organization_id: Filter by organization ID
 * - product_id: Filter by product ID
 * - status: Filter by status (error, warning, info, debug)
 * 
 * Success Response (200):
 * {
 *     "STATUS": "success",
 *     "MESSAGE": "Error logs retrieved successfully",
 *     "CODE": "200",
 *     "DATA": {
 *         "logs": [
 *             {
 *                 "uuid": "3f42a5b2-9e7f-4ac0-8c8b-7f2f7f3a2c1d",
 *                 "user_id": 1,
 *                 "organization_id": "xyz",
 *                 "product_id": 1,
 *                 "status": "error",
 *                 "message": "Validation failed",
 *                 "code": 422,
 *                 "data": {
 *                     "validation_errors": {
 *                         "email_address": "Email format is invalid"
 *                     }
 *                 },
 *                 "timestamp": "2024-01-15 14:25:30",
 *                 "created_at": "2024-01-15 14:25:30"
 *             }
 *         ],
 *         "pagination": {
 *             "current_page": 1,
 *             "items_per_page": 10,
 *             "total_items": 50,
 *             "total_pages": 5,
 *             "has_next_page": true,
 *             "has_previous_page": false
 *         }
 *     },
 *     "TIMESTAMP": "2024-01-15 14:25:30"
 * }
 * 
 * Error Responses:
 * - 400: Bad Request (Invalid parameters)
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
    // Get query parameters
    $page_number = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Validate pagination parameters
    if ($page_number < 1) {
        send_error_response('Page number must be greater than 0', 400);
    }
    
    if ($items_per_page < 1 || $items_per_page > 100) {
        send_error_response('Items per page must be between 1 and 100', 400);
    }
    
    // Build filters array
    $filters = [];
    
    if (!empty($_GET['user_id'])) {
        $filters['user_id'] = $_GET['user_id'];
    }
    
    if (!empty($_GET['organization_id'])) {
        $filters['organization_id'] = $_GET['organization_id'];
    }
    
    if (!empty($_GET['product_id'])) {
        $filters['product_id'] = $_GET['product_id'];
    }
    
    if (!empty($_GET['status'])) {
        $valid_statuses = ['error', 'warning', 'info', 'debug'];
        if (in_array($_GET['status'], $valid_statuses)) {
            $filters['status'] = $_GET['status'];
        } else {
            send_error_response('Invalid status value', 400);
        }
    }
    
    // Get paginated error logs
    $result = get_error_logs_paginated($page_number, $items_per_page, $filters);
    
    // Prepare response data
    $response_data = [
        'logs' => $result['data'],
        'pagination' => $result['pagination']
    ];
    
    // Send success response
    send_success_response('Error logs retrieved successfully', $response_data);
    
} catch (Exception $e) {
    // Log error and send generic error response
    error_log("Error in get_logs.php: " . $e->getMessage());
    send_error_response('An unexpected error occurred', 500);
}
?> 