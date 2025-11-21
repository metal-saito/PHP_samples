# Architecture Documentation

## Overview

This application follows modern PHP best practices with a layered architecture, emphasizing separation of concerns, testability, and maintainability.

## Architectural Layers

```
┌─────────────────────────────────────────┐
│          HTTP Layer (Public)            │
│  - Routing                              │
│  - Middleware (JSON, Rate Limit)        │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│       Controller Layer                  │
│  - Request validation                   │
│  - Response formatting                  │
│  - HTTP status codes                    │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│        Service Layer                    │
│  - Business logic                       │
│  - Transaction orchestration            │
│  - Domain model coordination            │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│       Repository Layer                  │
│  - Data persistence                     │
│  - Query building                       │
│  - Entity hydration                     │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│        Domain Layer                     │
│  - Entities (Task)                      │
│  - Value Objects                        │
│  - Domain logic                         │
└─────────────────────────────────────────┘
```

## Design Patterns

### 1. Repository Pattern

**Purpose**: Abstracts data access, making the domain layer independent of persistence mechanisms.

**Implementation**:
```php
class TaskRepository {
    public function save(Task $task): Task;
    public function findById(int $id): Task;
    public function findAll(): array;
}
```

**Benefits**:
- Testability: Easy to mock for unit tests
- Flexibility: Can switch databases without changing domain code
- Single Responsibility: Separates persistence from business logic

### 2. Service Layer Pattern

**Purpose**: Encapsulates business logic and coordinates between repositories and domain models.

**Implementation**:
```php
class TaskService {
    public function createTask(CreateTaskDTO $dto): Task;
    public function updateTask(int $id, UpdateTaskDTO $dto): Task;
    public function getStatistics(): array;
}
```

**Benefits**:
- Clear API: Single entry point for business operations
- Transaction management: Handles complex multi-step operations
- Validation orchestration: Coordinates validation across layers

### 3. Immutable Domain Models

**Purpose**: Ensures predictable state management and easier reasoning about code.

**Implementation**:
```php
final class Task {
    public function updateTitle(string $title): self {
        return new self(/* ... new state ... */);
    }
}
```

**Benefits**:
- Thread-safe: No shared mutable state
- Predictable: No unexpected side effects
- Easier testing: No need to reset state between tests
- Event sourcing ready: Natural fit for event-driven architectures

### 4. Data Transfer Objects (DTOs)

**Purpose**: Transfers data between layers without exposing domain logic.

**Implementation**:
```php
final class CreateTaskDTO {
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        // ...
    ) {}
}
```

**Benefits**:
- Type safety: Clear contract for data transfer
- Validation boundary: Clear place for input validation
- Decoupling: Controller doesn't depend on domain models

### 5. Dependency Injection

**Purpose**: Achieves loose coupling and easier testing.

**Implementation**:
```php
class TaskService {
    public function __construct(
        private TaskRepository $repository,
        private TaskValidator $validator
    ) {}
}
```

**Benefits**:
- Testability: Easy to inject mocks
- Flexibility: Easy to swap implementations
- Explicit dependencies: Clear what each class needs

## Domain Model Design

### Task Entity

The Task entity is the core domain model with rich behavior:

```php
- State Management: Status transitions with validation
- Business Rules: Overdue detection, completion status
- Invariants: Title length, valid status/priority values
- Immutability: All modifications return new instances
- Self-contained logic: No external dependencies
```

### State Machine

Task status transitions are validated:

```
pending → in_progress, cancelled
in_progress → completed, cancelled, pending
completed → (terminal)
cancelled → pending
```

This prevents invalid state transitions at the domain level.

## Database Design

### Schema

```sql
tasks
- id (PK)
- title
- description
- status
- priority
- due_date
- created_at
- updated_at

task_tags
- task_id (FK)
- tag
- PRIMARY KEY (task_id, tag)
```

### Indexes

Strategic indexes for common queries:
- `idx_tasks_status`: Status filtering
- `idx_tasks_priority`: Priority sorting
- `idx_tasks_due_date`: Overdue queries
- `idx_task_tags_tag`: Tag filtering

## Error Handling Strategy

### Exception Hierarchy

```
Exception
├── ValidationException (422)
├── NotFoundException (404)
└── DatabaseException (500)
```

### HTTP Status Code Mapping

- `200 OK`: Successful GET, PUT, PATCH
- `201 Created`: Successful POST
- `204 No Content`: Successful DELETE
- `400 Bad Request`: Malformed JSON
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Unexpected errors

## Middleware Pipeline

Requests flow through middleware:

1. **JsonMiddleware**: Sets JSON headers, handles errors
2. **RateLimitMiddleware**: Enforces request limits
3. **Router**: Dispatches to controller
4. **Controller**: Processes request

## Testing Strategy

### Unit Tests

Test individual components in isolation:
- Domain models (Task)
- Validators
- DTOs

Benefits:
- Fast execution
- No external dependencies
- Test business logic thoroughly

### Integration Tests

Test component interactions:
- Repository with database
- Service with repository
- End-to-end workflows

Benefits:
- Confidence in integration points
- Catch configuration issues
- Validate SQL queries

## Type Safety

### Strict Mode

All files use `declare(strict_types=1)` for maximum type safety.

### PHPStan Level 8

Strictest static analysis:
- No mixed types
- No missing type hints
- No unsafe operations
- Full nullability tracking

## Security Considerations

### Input Validation

Multiple layers:
1. **DTO validation**: Type-level safety
2. **Validator classes**: Business rule validation
3. **Domain model**: Invariant enforcement

### SQL Injection Prevention

- All queries use prepared statements
- Parameters properly typed and bound
- No string concatenation in SQL

### Rate Limiting

Prevents abuse:
- 100 requests per hour per IP
- Configurable limits
- Automatic cleanup of old entries

## Performance Optimizations

### Database

- Strategic indexes on frequently queried columns
- Pagination support to limit result sets
- Transaction management for consistency

### Autoloading

- PSR-4 autoloading
- Optimized autoloader in production
- No unnecessary file loads

### Query Optimization

- Single query for tasks with tags (JOIN)
- Batch operations where possible
- Efficient filtering at database level

## Extensibility Points

### Adding New Features

1. **New endpoints**: Add routes in `public/index.php`
2. **New validations**: Extend validator classes
3. **New queries**: Add methods to repository
4. **New business logic**: Add methods to service layer

### Supporting Multiple Databases

The repository pattern makes this straightforward:
1. Update connection in `config/database.php`
2. Adjust SQL dialects in repository if needed
3. No domain layer changes required

### Adding Authentication

Can be added as middleware:
```php
class AuthMiddleware {
    public function handle(callable $next): mixed {
        // Verify JWT token
        // Set user context
        return $next();
    }
}
```

## Best Practices Demonstrated

1. **SOLID Principles**
   - Single Responsibility: Each class has one reason to change
   - Open/Closed: Extensible through composition
   - Liskov Substitution: Proper inheritance usage
   - Interface Segregation: Small, focused interfaces
   - Dependency Inversion: Depend on abstractions

2. **Clean Code**
   - Meaningful names
   - Small, focused functions
   - Clear comments where needed
   - Consistent formatting

3. **DRY (Don't Repeat Yourself)**
   - Shared validation logic in validators
   - Reusable query methods in repository
   - Common error handling in middleware

4. **KISS (Keep It Simple, Stupid)**
   - No unnecessary abstractions
   - Simple routing mechanism
   - Straightforward dependency injection

## Conclusion

This architecture provides:
- **Maintainability**: Clear separation of concerns
- **Testability**: Easy to test each layer independently
- **Flexibility**: Easy to extend and modify
- **Type Safety**: Strong typing throughout
- **Performance**: Efficient database access
- **Security**: Multiple validation layers
