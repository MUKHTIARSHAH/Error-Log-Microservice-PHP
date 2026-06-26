# Error Log Microservice

A lightweight PHP microservice for collecting, storing, and querying application error logs through a REST API. Designed as a centralized logging service for small PHP applications.

**Repository:** [github.com/MUKHTIARSHAH/Error-Log-Microservice-PHP](https://github.com/MUKHTIARSHAH/Error-Log-Microservice-PHP)

---

## Feature Status

| Feature | Status |
|---------|--------|
| Error log ingestion | ✅ Implemented |
| Pagination & filtering | ✅ Implemented |
| Statistics endpoint | ✅ Implemented |
| Swagger documentation | ✅ Implemented |
| CORS support | ✅ Implemented |
| Client IP capture | ✅ Implemented |
| JWT authentication | ⚠️ Planned |
| RabbitMQ integration | ⚠️ Dependencies only |
| Memcache caching | ⚠️ Planned |
| PHPUnit tests | ⚠️ Project configured, no test suite yet |

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| Runtime | PHP 7.4+ (procedural) |
| Database | MySQL 5.7+ with JSON columns |
| Data access | PDO with prepared statements |
| Dependencies | Composer |
| API docs | Swagger UI |

---

## Architecture

```
Client Application
        │
        ▼
  REST Endpoint (api/v1/error_logs/)
        │
        ▼
  Input Validation (validation_helpers.php)
        │
        ▼
  Business Logic (functions.php)
        │
        ▼
  MySQL Database (errors table)
        │
        ▼
  Standardized JSON Response (response_helpers.php)
```

---

## Design Principles

- **RESTful API** — Resource-oriented endpoints with clear HTTP methods
- **Standardized JSON responses** — Consistent `STATUS`, `MESSAGE`, `CODE`, `DATA`, and `TIMESTAMP` envelope across all endpoints
- **Prepared statements** — All database queries use PDO parameter binding
- **Separation of concerns** — Endpoints, validation, business logic, and response formatting live in dedicated modules
- **JSON metadata support** — Arbitrary context stored in a `data` JSON column per log entry
- **Input sanitization** — User-supplied values are validated and sanitized before persistence

---

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Apache or Nginx with PHP support

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/MUKHTIARSHAH/Error-Log-Microservice-PHP.git
   cd Error-Log-Microservice-PHP
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure the database**
   - Update credentials in `config/database.php`
   - Import the schema:
     ```bash
     mysql -u your_username -p < database_schema.sql
     ```

4. **Set log directory permissions**
   ```bash
   chmod -R 755 logs/
   ```

5. **Browse the API documentation**
   ```
   http://localhost/error_logs/docs/swagger/
   ```

---

## Database

The service stores logs in a MySQL `errors` table with fields for:

- `user_id` — ID of the user associated with the error
- `organization_id` — Tenant or organization identifier
- `product_id` — Application or product that produced the error
- `status` — Severity level (`error`, `warning`, `info`, `debug`)
- `message` — Human-readable error description
- `code` — Numeric error code
- `data` — JSON metadata for additional context
- `timestamp` — When the error occurred (from the client)
- `ip_address` — Client IP captured server-side
- `created_at` / `updated_at` — Record lifecycle timestamps

See [`database_schema.sql`](database_schema.sql) for the complete schema, indexes, views, and stored procedures.

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/error_logs/create_log.php` | Create a new error log |
| `GET` | `/api/v1/error_logs/get_logs.php` | List logs with pagination and filters |
| `GET` | `/api/v1/error_logs/get_log.php?id={id}` | Retrieve a single log by ID |
| `GET` | `/api/v1/error_logs/statistics.php` | Aggregate error counts and breakdowns |

### Example — Create Error Log

**`POST /api/v1/error_logs/create_log.php`**

Request:
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
            "email_address": "Email format is invalid"
        }
    },
    "TIMESTAMP": "2024-01-15 14:25:30"
}
```

Response (`201`):
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

All endpoints return the same response envelope. Error responses use `"STATUS": "error"` with an appropriate HTTP status code (`400`, `404`, `405`, `422`, or `500`).

Full request/response details are available in the [Swagger UI](docs/swagger/).

---

## Project Structure

```
error_logs/
├── api/v1/error_logs/       # REST endpoints
│   ├── create_log.php
│   ├── get_logs.php
│   ├── get_log.php
│   └── statistics.php
├── config/
│   └── database.php         # Database connection settings
├── includes/
│   ├── functions.php        # Core business logic
│   ├── validation_helpers.php
│   ├── response_helpers.php
│   └── auth_helpers.php     # Placeholder for future JWT auth
├── docs/swagger/            # Swagger UI
├── logs/                    # Runtime log output
├── tests/                   # Test suite (not yet implemented)
├── database_schema.sql
├── composer.json
└── composer.lock
```

---

## Testing

PHPUnit is listed as a dev dependency and can be invoked via:

```bash
composer test
```

No test files exist yet. The `tests/` directory is reserved for future coverage.

---

## Security

- **Prepared statements** — SQL injection mitigation on all queries
- **Input validation** — Required fields, type checks, and length constraints before writes
- **Input sanitization** — String values cleaned via `sanitize_input()`
- **CORS headers** — Cross-origin access enabled for API consumers
- **Credential isolation** — Database credentials belong in `config/database.php` and should not be committed with real values

JWT authentication is planned but not yet enforced on any endpoint.

---

## Contributing

This is a personal portfolio repository. It is not open for contributions, forks, or external use.

---

## License

**All Rights Reserved** — Copyright (c) 2024–2026 Mukhtiar Shah

This project is shared **for recruiter and hiring review only**. You may view the code to evaluate the author's skills. You may not use, copy, download, modify, distribute, or incorporate any part of this software without explicit written permission.

See [LICENSE](LICENSE) for full terms.
