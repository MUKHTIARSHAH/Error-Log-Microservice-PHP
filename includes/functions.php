<?php
/**
 * File: functions.php
 * Purpose: General helper functions for error_logs microservice
 * Created: 2024-06-08
 * Author: Development Team
 * Last Modified: 2024-06-08
 */

// Include validation helpers to access sanitize_input function
require_once __DIR__ . '/validation_helpers.php';

/**
 * Get client IP address (handles proxies, load balancers, etc.)
 * @return string Client IP address
 */
function get_client_ip_address() {
    // Check for IP address in various headers (for proxies/load balancers)
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',             // Proxy
        'HTTP_X_FORWARDED_FOR',       // Load balancer/proxy
        'HTTP_X_FORWARDED',           // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',   // Cluster
        'HTTP_FORWARDED_FOR',         // Proxy
        'HTTP_FORWARDED',             // Proxy
        'REMOTE_ADDR'                 // Standard
    ];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            
            // Handle comma-separated IPs (take the first one)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP address
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback to REMOTE_ADDR (may be private IP behind proxy)
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Generate a UUID v4 string
 * @return string UUID (36-char)
 */
function generate_uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    $hex = bin2hex($data);
    return sprintf('%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}

/**
 * Create error log in database
 * @param array $error_data Error log data
 * @return int|false Error log ID or false on failure
 */
function create_error_log($error_data) {
    try {
        $pdo = get_database_connection();
        
        // Get client IP address
        $client_ip = get_client_ip_address();
        
        // Prepare data for insertion
        $log_data = [
            'user_id' => (int)$error_data['user_id'],
            'organization_id' => sanitize_input($error_data['organization_id']),
            'product_id' => (int)$error_data['product_id'],
            'status' => sanitize_input($error_data['STATUS']),
            'message' => sanitize_input($error_data['MESSAGE']),
            'code' => (int)$error_data['CODE'],
            'data' => isset($error_data['DATA']) ? json_encode($error_data['DATA']) : null,
            'timestamp' => $error_data['TIMESTAMP'],
            'ip_address' => $client_ip,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Prepare SQL query
        $sql = "INSERT INTO errors (user_id, organization_id, product_id, status, message, code, data, timestamp, ip_address, created_at) 
                VALUES (:user_id, :organization_id, :product_id, :status, :message, :code, :data, :timestamp, :ip_address, :created_at)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($log_data);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Database error in create_error_log: " . $e->getMessage());
        return false;
    }
}

/**
 * Get error log by ID
 * @param int $log_id Error log ID
 * @return array|false Error log data or false if not found
 */
function get_error_log_by_id($log_id) {
    try {
        $pdo = get_database_connection();
        
        $sql = "SELECT * FROM errors WHERE id = :log_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':log_id', $log_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $log_data = $stmt->fetch();
        
        if ($log_data) {
            // Decode JSON data if present
            if ($log_data['data']) {
                $log_data['data'] = json_decode($log_data['data'], true);
            }
        }
        
        return $log_data ? $log_data : false;
        
    } catch (PDOException $e) {
        error_log("Database error in get_error_log_by_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Get paginated error logs
 * @param int $page_number Current page number (starting from 1)
 * @param int $items_per_page Number of items per page
 * @param array $filters Optional filters (user_id, organization_id, product_id, status)
 * @return array Paginated results with metadata
 */
function get_error_logs_paginated($page_number = 1, $items_per_page = 10, $filters = []) {
    try {
        $pdo = get_database_connection();
        
        // Calculate offset
        $offset = ($page_number - 1) * $items_per_page;
        
        // Build WHERE clause for filters
        $where_conditions = [];
        $where_params = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "user_id = :user_id";
            $where_params['user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['organization_id'])) {
            $where_conditions[] = "organization_id = :organization_id";
            $where_params['organization_id'] = sanitize_input($filters['organization_id']);
        }
        
        if (!empty($filters['product_id'])) {
            $where_conditions[] = "product_id = :product_id";
            $where_params['product_id'] = (int)$filters['product_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_conditions[] = "status = :status";
            $where_params['status'] = sanitize_input($filters['status']);
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total_count FROM errors " . $where_clause;
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($where_params);
        $total_count = $count_stmt->fetch()['total_count'];
        
        // Get paginated data
        $data_sql = "SELECT * FROM errors " . $where_clause . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $data_stmt = $pdo->prepare($data_sql);
        
        // Bind filter parameters
        foreach ($where_params as $key => $value) {
            $data_stmt->bindValue(':' . $key, $value);
        }
        
        $data_stmt->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
        $data_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $data_stmt->execute();
        
        $logs_data = $data_stmt->fetchAll();
        
        // Decode JSON data for each log
        foreach ($logs_data as &$log) {
            if ($log['data']) {
                $log['data'] = json_decode($log['data'], true);
            }
        }
        
        // Calculate pagination metadata
        $total_pages = ceil($total_count / $items_per_page);
        
        return [
            'data' => $logs_data,
            'pagination' => [
                'current_page' => $page_number,
                'items_per_page' => $items_per_page,
                'total_items' => $total_count,
                'total_pages' => $total_pages,
                'has_next_page' => $page_number < $total_pages,
                'has_previous_page' => $page_number > 1
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Database error in get_error_logs_paginated: " . $e->getMessage());
        return [
            'data' => [],
            'pagination' => []
        ];
    }
}

/**
 * Update error log
 * @param string $log_uuid Error log UUID
 * @param array $update_data Data to update
 * @return bool Success status
 */
function update_error_log($log_uuid, $update_data) {
    try {
        $pdo = get_database_connection();
        
        // Prepare update data
        $allowed_fields = ['status', 'message', 'code', 'data'];
        $update_fields = [];
        $parameters = [];
        
        foreach ($update_data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $update_fields[] = $field . " = :" . $field;
                
                if ($field === 'data' && is_array($value)) {
                    $parameters[$field] = json_encode($value);
                } else {
                    $parameters[$field] = $value;
                }
            }
        }
        
        if (empty($update_fields)) {
            return false;
        }
        
        $parameters['log_uuid'] = $log_uuid;
        $parameters['updated_at'] = date('Y-m-d H:i:s');
        
        // Add updated_at to update fields
        $update_fields[] = "updated_at = :updated_at";
        
        $sql = "UPDATE errors SET " . implode(', ', $update_fields) . " WHERE uuid = :log_uuid";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parameters);
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        error_log("Database error in update_error_log: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete error log
 * @param string $log_uuid Error log UUID
 * @return bool Success status
 */
function delete_error_log($log_uuid) {
    try {
        $pdo = get_database_connection();
        
        $sql = "DELETE FROM errors WHERE uuid = :log_uuid";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':log_uuid', $log_uuid, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        error_log("Database error in delete_error_log: " . $e->getMessage());
        return false;
    }
}

/**
 * Get error log statistics
 * @param array $filters Optional filters
 * @return array Statistics data
 */
function get_error_log_statistics($filters = []) {
    try {
        $pdo = get_database_connection();
        
        // Build WHERE clause for filters
        $where_conditions = [];
        $where_params = [];
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = "user_id = :user_id";
            $where_params['user_id'] = (int)$filters['user_id'];
        }
        
        if (!empty($filters['organization_id'])) {
            $where_conditions[] = "organization_id = :organization_id";
            $where_params['organization_id'] = sanitize_input($filters['organization_id']);
        }
        
        if (!empty($filters['product_id'])) {
            $where_conditions[] = "product_id = :product_id";
            $where_params['product_id'] = (int)$filters['product_id'];
        }
        
        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get total count
        $total_sql = "SELECT COUNT(*) as total FROM errors " . $where_clause;
        $total_stmt = $pdo->prepare($total_sql);
        $total_stmt->execute($where_params);
        $total_count = $total_stmt->fetch()['total'];
        
        // Get status breakdown
        $status_sql = "SELECT status, COUNT(*) as count FROM errors " . $where_clause . " GROUP BY status";
        $status_stmt = $pdo->prepare($status_sql);
        $status_stmt->execute($where_params);
        $status_breakdown = $status_stmt->fetchAll();
        
        // Get recent errors (last 24 hours)
        $recent_sql = "SELECT COUNT(*) as recent_count FROM errors " . $where_clause . 
                     (empty($where_conditions) ? " WHERE" : " AND") . " created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $recent_stmt = $pdo->prepare($recent_sql);
        $recent_stmt->execute($where_params);
        $recent_count = $recent_stmt->fetch()['recent_count'];
        
        return [
            'total_errors' => (int)$total_count,
            'recent_errors_24h' => (int)$recent_count,
            'status_breakdown' => $status_breakdown
        ];
        
    } catch (PDOException $e) {
        error_log("Database error in get_error_log_statistics: " . $e->getMessage());
        return [
            'total_errors' => 0,
            'recent_errors_24h' => 0,
            'status_breakdown' => []
        ];
    }
}
?>