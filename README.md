# Error Log Microservice

A PHP-based microservice for managing and storing error logs with RESTful API endpoints. This service accepts error logs in JSON format and stores them in a MySQL database for analysis and monitoring.

## Technology Stack

- **Backend**: Core PHP (Procedural) with PDO
- **Database**: MySQL
- **Authentication**: JWT (JSON Web Tokens) - *configured but not implemented*
- **Queue System**: RabbitMQ - *dependencies included but not implemented*
- **Caching**: Memcache - *dependencies included but not implemented*
- **API Documentation**: Swagger UI
- **Testing**: PHPUnit

## Features

- ✅ Store error logs with structured data
- ✅ Retrieve logs with pagination and filtering
- ✅ Get error statistics and analytics
- ✅ JSON data support for complex error information
- ✅ Input validation and sanitization
- ✅ Standardized API responses
- ✅ Comprehensive error handling
- ✅ CORS support for web applications
- ✅ Swagger UI documentation

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Memcache (optional)
- RabbitMQ (optional)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd error_logs
   ```

2. **Install Composer dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   - Update database credentials in `config/database.php`
   - Import the database schema: `database_schema.sql`
   ```bash
   mysql -u your_username -p < database_schema.sql
   ```

4. **Set up environment**
   - Configure database credentials in `config/database.php`
   - JWT, Memcache, and RabbitMQ configuration files are referenced in composer.json but not yet created

5. **Set permissions**
   ```bash
   chmod -R 755 logs/
   ```

## Database Schema

The microservice uses a single table `error` with the following structure:

```sql
CREATE TABLE `error` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `organization_id` VARCHAR(255) NOT NULL,
    `product_id` INT(11) NOT NULL,
    `status` ENUM('error', 'warning', 'info', 'debug') NOT NULL DEFAULT 'error',
    `message` TEXT NOT NULL,
    `code` INT(11) NOT NULL,
    `data` JSON NULL,
    `timestamp` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);
```

## API Endpoints

### 1. Create Error Log
**POST** `/api/v1/error_logs/create_log.php`

Creates a new error log entry.

**Request Body:**
```json
{
    "user_id": "1",
    "organization_id": "xyz",
    "product_id": "1",
    "STATUS": "error",
    "MESSAGE": "Validation failed",
    "CODE": "422",
    "DATA": {
        "validation_errors": {
            "email_address": "Email format is invalid",
            "password": "Password must be at least 8 characters"
        }
    },
    "TIMESTAMP": "2024-01-15 14:25:30"
}
```

**Response:**
```json
{
    "STATUS": "success",
    "MESSAGE": "Error log created successfully",
    "CODE": "201",
    "DATA": {
        "log_id": 123,
        "created_at": "2024-01-15 14:25:30"
    },
    "TIMESTAMP": "2024-01-15 14:25:30"
}
```

### 2. Get Error Logs
**GET** `/api/v1/error_logs/get_logs.php`

Retrieves error logs with pagination and filtering.

**Query Parameters:**
- `page`: Page number (default: 1)
- `limit`: Items per page (default: 10, max: 100)
- `user_id`: Filter by user ID
- `organization_id`: Filter by organization ID
- `product_id`: Filter by product ID
- `status`: Filter by status (error, warning, info, debug)

**Example:**
```
GET /api/v1/error_logs/get_logs.php?page=1&limit=10&user_id=1&status=error
```

### 3. Get Error Log by ID
**GET** `/api/v1/error_logs/get_log.php?id={log_id}`

Retrieves a specific error log by its ID.

**Example:**
```
GET /api/v1/error_logs/get_log.php?id=123
```

### 4. Get Statistics
**GET** `/api/v1/error_logs/statistics.php`

Retrieves error log statistics and analytics.

**Query Parameters:**
- `user_id`: Filter by user ID
- `organization_id`: Filter by organization ID
- `product_id`: Filter by product ID

**Response:**
```json
{
    "STATUS": "success",
    "MESSAGE": "Statistics retrieved successfully",
    "CODE": "200",
    "DATA": {
        "total_errors": 150,
        "recent_errors_24h": 25,
        "status_breakdown": [
            {
                "status": "error",
                "count": 100
            },
            {
                "status": "warning",
                "count": 30
            }
        ]
    },
    "TIMESTAMP": "2024-01-15 14:25:30"
}
```

## Usage Examples

### PHP Example
```php
<?php
// Create error log
$error_data = [
    "user_id" => "1",
    "organization_id" => "xyz",
    "product_id" => "1",
    "STATUS" => "error",
    "MESSAGE" => "Database connection failed",
    "CODE" => "500",
    "DATA" => [
        "connection_string" => "mysql://localhost:3306/db",
        "error_code" => "2002"
    ],
    "TIMESTAMP" => date('Y-m-d H:i:s')
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/error_logs/api/v1/error_logs/create_log.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($error_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
echo "Error log created with ID: " . $result['DATA']['log_id'];
?>
```

### JavaScript Example
```javascript
// Create error log
const errorData = {
    "user_id": "1",
    "organization_id": "xyz",
    "product_id": "1",
    "STATUS": "error",
    "MESSAGE": "API request failed",
    "CODE": "400",
    "DATA": {
        "endpoint": "/api/users",
        "method": "POST",
        "request_id": "req_123"
    },
    "TIMESTAMP": new Date().toISOString().slice(0, 19).replace('T', ' ')
};

fetch('http://localhost/error_logs/api/v1/error_logs/create_log.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(errorData)
})
.then(response => response.json())
.then(data => {
    console.log('Error log created:', data);
})
.catch(error => {
    console.error('Error:', error);
});
```

## Testing

### PHPUnit Tests
```bash
composer test
```

### API Documentation
Access the Swagger UI documentation by navigating to:
```
http://your-domain/error_logs/docs/swagger/
```

## Project Structure

```
error_logs/
├── config/                 # Configuration files
│   └── database.php       # Database configuration
├── includes/              # Helper functions
│   ├── functions.php      # Error log functions
│   ├── auth_helpers.php   # Authentication helpers
│   ├── response_helpers.php # API response helpers
│   └── validation_helpers.php # Input validation
├── api/                   # API endpoints
│   └── v1/
│       └── error_logs/    # Error logs API endpoints
│           ├── create_log.php
│           ├── get_logs.php
│           ├── get_log.php
│           └── statistics.php
├── logs/                  # Application logs (empty)
├── docs/                  # Documentation
│   └── swagger/           # Swagger UI documentation
│       ├── index.html
│       └── .htaccess
├── tests/                 # Unit tests (empty)
├── database_schema.sql    # Database schema
├── composer.json          # Composer configuration
├── composer.lock          # Composer lock file
├── .gitignore            # Git ignore file
└── vendor/                # Composer dependencies
```

## Development

### Running Tests
```bash
composer test
```

### Code Standards
This project follows PHP best practices:
- PSR-4 autoloading
- Consistent naming conventions
- Proper error handling
- Input validation and sanitization
- Security best practices
- CORS support for web applications

## API Response Format

All API endpoints return standardized JSON responses with the following structure:

### Success Response
```json
{
    "STATUS": "success",
    "MESSAGE": "Operation completed successfully",
    "CODE": "200",
    "DATA": {},
    "TIMESTAMP": "2024-01-15 14:25:30"
}
```

### Error Response
```json
{
    "STATUS": "error",
    "MESSAGE": "Error description",
    "CODE": "400",
    "DATA": {},
    "TIMESTAMP": "2024-01-15 14:25:30"
}
```

## Error Handling

The microservice includes comprehensive error handling:

- **400 Bad Request**: Invalid input data or parameters
- **404 Not Found**: Requested resource not found
- **405 Method Not Allowed**: Unsupported HTTP method
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server-side errors

All errors are logged to PHP's error log for debugging.

## Security Features

- Input validation and sanitization
- SQL injection prevention with prepared statements
- CORS headers for cross-origin requests
- Error message sanitization
- Structured error logging

## Contributing

1. Follow the established coding standards
2. Write tests for new features
3. Update documentation
4. Submit pull requests

## License

MIT License - see LICENSE file for details 