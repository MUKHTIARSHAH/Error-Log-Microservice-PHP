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
 * API Endpoint: Get Error Log Statistics
 * 
 * Description: Retrieves error log statistics and analytics
 * Method: GET
 * URL: /api/v1/error_logs/statistics.php
 * 
 * Query Parameters:
 * - user_id: Filter by user ID
 * - organization_id: Filter by organization ID
 * - product_id: Filter by product ID
 * 
 * Success Response (200):
 * {
 *     "STATUS": "success",
 *     "MESSAGE": "Statistics retrieved successfully",
 *     "CODE": "200",
 *     "DATA": {
 *         "total_errors": 150,
 *         "recent_errors_24h": 25,
 *         "status_breakdown": [
 *             {
 *                 "status": "error",
 *                 "count": 100
 *             },
 *             {
 *                 "status": "warning",
 *                 "count": 30
 *             },
 *             {
 *                 "status": "info",
 *                 "count": 20
 *             }
 *         ]
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
    
    // Get error log statistics
    $statistics = get_error_log_statistics($filters);
    
    // Send success response
    send_success_response('Statistics retrieved successfully', $statistics);
    
} catch (Exception $e) {
    // Log error and send generic error response
    error_log("Error in statistics.php: " . $e->getMessage());
    send_error_response('An unexpected error occurred', 500);
}
?> 