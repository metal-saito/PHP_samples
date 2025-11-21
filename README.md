# Task Management REST API

A modern, well-structured PHP REST API for task management with advanced features.

## Features

- **RESTful API Design**: Clean, intuitive endpoints following REST best practices
- **Domain-Driven Design**: Rich domain models with business logic encapsulation
- **Immutable Entities**: Task entities are immutable for better predictability
- **Repository Pattern**: Clean separation between domain and data access layers
- **Service Layer**: Business logic encapsulation with dependency injection
- **Validation**: Comprehensive input validation with detailed error messages
- **State Machine**: Task status transitions with validation
- **Rate Limiting**: Built-in rate limiting middleware (100 requests/hour)
- **CORS Support**: Cross-Origin Resource Sharing enabled
- **Tag System**: Flexible tagging for task organization
- **Advanced Filtering**: Filter by status, tag, overdue status
- **Statistics**: Comprehensive task statistics endpoint
- **Unit & Integration Tests**: Full test coverage with PHPUnit
- **Static Analysis**: PHPStan level 8 for maximum type safety
- **PSR Standards**: Following PSR-4 (autoloading), PSR-12 (coding style)

## Architecture

```
src/
├── Controller/      # HTTP request handling
├── Service/         # Business logic layer
├── Repository/      # Data persistence layer
├── Model/           # Domain entities
├── DTO/             # Data Transfer Objects
├── Validator/       # Input validation
├── Middleware/      # HTTP middleware
└── Exception/       # Custom exceptions
```

## Requirements

- PHP 8.1 or higher
- PDO extension
- JSON extension
- Composer (for dependencies)

## Installation

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse
```

## Quick Start

```bash
# Start built-in PHP server
php -S localhost:8000 -t public

# Or using PHP built-in server with routing
cd public && php -S localhost:8000
```

## API Endpoints

### Task Management

- `GET /api/tasks` - List all tasks (supports pagination)
  - Query parameters: `?limit=N&offset=N`
- `GET /api/tasks/{id}` - Get task by ID
- `POST /api/tasks` - Create new task
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task

### Filtering & Statistics

- `GET /api/statistics` - Get task statistics
- `GET /api/tasks/overdue/list` - Get overdue tasks
- `GET /api/tasks/status/{status}` - Get tasks by status
  - Valid statuses: `pending`, `in_progress`, `completed`, `cancelled`
- `GET /api/tasks/tag/{tag}` - Get tasks by tag

### Tag Management

- `POST /api/tasks/{id}/tags` - Add tags to task
- `DELETE /api/tasks/{id}/tags/{tag}` - Remove tag from task

### System

- `GET /api/health` - Health check endpoint
- `GET /` - API documentation

## Usage Examples

### Create a Task

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Implement user authentication",
    "description": "Add JWT-based authentication to the API",
    "priority": "high",
    "due_date": "2024-12-31 23:59:59",
    "tags": ["backend", "security"]
  }'
```

### Update Task Status

```bash
curl -X PUT http://localhost:8000/api/tasks/1 \
  -H "Content-Type: application/json" \
  -d '{
    "status": "in_progress"
  }'
```

### Get Statistics

```bash
curl http://localhost:8000/api/statistics
```

### Filter by Status

```bash
curl http://localhost:8000/api/tasks/status/pending
```

## Task Properties

### Status Values
- `pending` - Initial state
- `in_progress` - Task is being worked on
- `completed` - Task is finished
- `cancelled` - Task is cancelled

### Priority Levels
- `low` - Low priority
- `medium` - Medium priority (default)
- `high` - High priority
- `urgent` - Urgent priority

### Status Transitions

```
pending → in_progress, cancelled
in_progress → completed, cancelled, pending
completed → (final state)
cancelled → pending
```

## Testing

```bash
# Run all tests
composer test

# Run specific test suite
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration

# Run with coverage (requires Xdebug)
vendor/bin/phpunit --coverage-html coverage
```

## Static Analysis

```bash
# Run PHPStan
composer analyse

# Or directly
vendor/bin/phpstan analyse
```

## Design Patterns Used

1. **Repository Pattern** - Data access abstraction
2. **Service Layer Pattern** - Business logic encapsulation
3. **Factory Pattern** - Task creation
4. **Dependency Injection** - Loose coupling
5. **Immutable Objects** - Predictable state management
6. **DTO Pattern** - Data transfer between layers
7. **Middleware Pattern** - Request/response processing
8. **State Machine** - Task status management

## Key Technical Highlights

### 1. Immutable Domain Models
Tasks are immutable - any modification creates a new instance, ensuring predictable behavior and easier testing.

### 2. Type Safety
- Strict types enabled (`declare(strict_types=1)`)
- PHPStan level 8 compliance
- Full type hints on all methods
- Readonly properties on DTOs

### 3. Error Handling
- Custom exception hierarchy
- Proper HTTP status codes
- Detailed error messages
- Transaction management

### 4. Database Design
- Normalized schema
- Proper indexes for performance
- Foreign key constraints
- Support for SQLite and MySQL

### 5. Testability
- Dependency injection throughout
- Interface-based design where appropriate
- In-memory SQLite for integration tests
- Comprehensive test coverage

## Configuration

Database configuration can be customized via environment variables:

```bash
DB_DRIVER=sqlite       # or mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tasks
DB_USERNAME=root
DB_PASSWORD=secret
```

## Security Features

- Input validation at multiple layers
- Rate limiting to prevent abuse
- Prepared statements to prevent SQL injection
- Type-safe parameter handling
- No sensitive data in error messages (production mode)

## Performance Considerations

- Database indexes on frequently queried columns
- Efficient pagination support
- Optimized autoloader configuration
- Connection pooling support
- Lazy loading of dependencies

## License

MIT License
