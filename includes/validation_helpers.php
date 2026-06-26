<?php
/**
 * File: validation_helpers.php
 * Purpose: Input validation helper functions for error_logs microservice
 * Created: 2024-06-08
 * Author: Development Team
 * Last Modified: 2024-06-08
 */

/**
 * Validate required field
 * @param mixed $value Value to validate
 * @param string $field_name Name of the field for error message
 * @return string|null Error message or null if valid
 */
function validate_required_field($value, $field_name) {
    if (empty($value) && $value !== '0' && $value !== 0) {
        return ucfirst($field_name) . ' is required';
    }
    return null;
}

/**
 * Validate string length
 * @param string $value String to validate
 * @param int $min_length Minimum length
 * @param int $max_length Maximum length
 * @param string $field_name Field name for error message
 * @return string|null Error message or null if valid
 */
function validate_string_length($value, $min_length, $max_length, $field_name) {
    $length = strlen($value);
    
    if ($length < $min_length) {
        return ucfirst($field_name) . ' must be at least ' . $min_length . ' characters';
    }
    
    if ($length > $max_length) {
        return ucfirst($field_name) . ' must not exceed ' . $max_length . ' characters';
    }
    
    return null;
}

/**
 * Validate numeric value
 * @param mixed $value Value to validate
 * @param string $field_name Field name for error message
 * @return string|null Error message or null if valid
 */
function validate_numeric_value($value, $field_name) {
    if (!is_numeric($value)) {
        return ucfirst($field_name) . ' must be a valid number';
    }
    return null;
}

/**
 * Validate email address format
 * @param string $email_address The email address to validate
 * @return bool True if valid, false otherwise
 */
function validate_email_format($email_address) {
    if (empty($email_address)) {
        return false;
    }
    
    if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Sanitize user input to prevent XSS attacks
 * @param string $input_data Raw input data
 * @return string Sanitized input data
 */
function sanitize_input($input_data) {
    $sanitized = trim($input_data);
    $sanitized = stripslashes($sanitized);
    $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
    return $sanitized;
}

/**
 * Validate error log data structure
 * @param array $error_data Error log data to validate
 * @return array Validation errors array
 */
function validate_error_log_data($error_data) {
    $validation_errors = [];
    
    // Validate required fields
    $required_fields = ['user_id', 'organization_id', 'product_id', 'STATUS', 'MESSAGE', 'CODE', 'TIMESTAMP'];
    
    foreach ($required_fields as $field) {
        $error = validate_required_field($error_data[$field] ?? '', $field);
        if ($error) {
            $validation_errors[$field] = $error;
        }
    }
    
    // Validate numeric fields
    $numeric_fields = ['user_id', 'product_id'];
    foreach ($numeric_fields as $field) {
        if (isset($error_data[$field])) {
            $error = validate_numeric_value($error_data[$field], $field);
            if ($error) {
                $validation_errors[$field] = $error;
            }
        }
    }
    
    // Validate STATUS field
    if (isset($error_data['STATUS'])) {
        $valid_statuses = ['error', 'warning', 'info', 'debug'];
        if (!in_array($error_data['STATUS'], $valid_statuses)) {
            $validation_errors['STATUS'] = 'Status must be one of: ' . implode(', ', $valid_statuses);
        }
    }
    
    // Validate CODE field (should be numeric)
    if (isset($error_data['CODE'])) {
        $error = validate_numeric_value($error_data['CODE'], 'CODE');
        if ($error) {
            $validation_errors['CODE'] = $error;
        }
    }
    
    // Validate TIMESTAMP format
    if (isset($error_data['TIMESTAMP'])) {
        $timestamp = $error_data['TIMESTAMP'];
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp)) {
            $validation_errors['TIMESTAMP'] = 'Timestamp must be in format: YYYY-MM-DD HH:MM:SS';
        }
    }
    
    // Validate DATA field (should be array if present)
    if (isset($error_data['DATA']) && !is_array($error_data['DATA'])) {
        $validation_errors['DATA'] = 'DATA field must be an array';
    }
    
    return $validation_errors;
}
?>