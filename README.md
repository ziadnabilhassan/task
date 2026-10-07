# Multi-Tenant Task Management API

A RESTful JSON API for managing tasks in a **multi-tenant environment** using Laravel 12 and Laravel Sanctum.

Each user belongs to a company, and users can only view and manage tasks that belong to their own company.

Authentication is handled using **Laravel Sanctum Personal Access Tokens**.

---

## Tech Stack

* PHP 8.2+
* Laravel 12
* Laravel Sanctum 4
* MySQL 8.0+ / MariaDB 10.6+
* RESTful JSON API
* Eloquent ORM
* Form Requests
* API Resources
* Service Layer

---

## Requirements

Before installing the project, make sure you have:

* PHP 8.2 or higher
* Composer 2
* MySQL 8.0+ or MariaDB 10.6+
* Required PHP extensions:

  * `pdo_mysql`
  * `mbstring`
  * `openssl`
  * `tokenizer`
  * `xml`
  * `ctype`
  * `json`

---

# Installation

Clone the repository and install the dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

---

# Environment Configuration

Open the `.env` file and configure your database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=
```

### Optional Sanctum Configuration

You can configure token expiration using:

```env
SANCTUM_TOKEN_EXPIRATION=60
```

The value is specified in minutes.

If `SANCTUM_TOKEN_EXPIRATION` is not configured, tokens do not expire.

---

# Database Setup

Create an empty database:

```sql
CREATE DATABASE task_management
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Or using MySQL CLI:

```bash
mysql -u root -e "CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Run the migrations:

```bash
php artisan migrate
```

The migrations create:

* `companies`
* `users`
* `tasks`
* `personal_access_tokens`

The `users` table includes:

* `company_id`
* `role`

---

# Sanctum Authentication

Laravel Sanctum is already configured.

The project includes:

* `HasApiTokens` on the `User` model
* Sanctum personal access token migration
* `auth:sanctum` middleware
* API routes registered in `bootstrap/app.php`

Authenticated requests must include:

```http
Authorization: Bearer <token>
```

---

# Running the Project

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at:

```text
http://localhost:8000/api
```

---

# Creating Initial Users

The project does not include seeders.

You can create the initial companies and users using Laravel Tinker:

```bash
php artisan tinker
```

Then run:

```php
$a = App\Models\Company::create([
    'name' => 'Company A'
]);

$b = App\Models\Company::create([
    'name' => 'Company B'
]);

App\Models\User::create([
    'company_id' => $a->id,
    'name' => 'Alice',
    'email' => 'alice@company-a.test',
    'password' => 'secret123',
    'role' => 'admin',
]);

App\Models\User::create([
    'company_id' => $a->id,
    'name' => 'Adam',
    'email' => 'adam@company-a.test',
    'password' => 'secret123',
    'role' => 'user',
]);

App\Models\User::create([
    'company_id' => $b->id,
    'name' => 'Bella',
    'email' => 'bella@company-b.test',
    'password' => 'secret123',
    'role' => 'manager',
]);

App\Models\User::create([
    'company_id' => $b->id,
    'name' => 'Ben',
    'email' => 'ben@company-b.test',
    'password' => 'secret123',
    'role' => 'user',
]);
```

Passwords are automatically hashed by the `User` model.

---

# API Endpoints

| Method    | Endpoint          | Authentication | Description                         |
| --------- | ----------------- | -------------- | ----------------------------------- |
| POST      | `/api/login`      | No             | Authenticate user and receive token |
| GET       | `/api/tasks`      | Yes            | List company tasks                  |
| POST      | `/api/tasks`      | Yes            | Create a task                       |
| GET       | `/api/tasks/{id}` | Yes            | Show a task                         |
| PUT/PATCH | `/api/tasks/{id}` | Yes            | Update a task                       |
| DELETE    | `/api/tasks/{id}` | Yes            | Delete a task                       |

All API requests should include:

```http
Accept: application/json
```

Authenticated requests must also include:

```http
Authorization: Bearer <token>
```

---

# Authentication

## Login

### Request

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@company-a.test","password":"secret123"}'
```

### Successful Response — 200

```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "token": "1|abcdef...",
        "token_type": "Bearer",
        "user": {
            "id": 1,
            "company_id": 1,
            "name": "Alice",
            "email": "alice@company-a.test",
            "role": "admin"
        }
    }
}
```

### Invalid Credentials — 401

```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

---

# Task Management

## Create Task

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{
    "title": "Redesign website",
    "description": "New landing page",
    "priority": "high",
    "due_date": "2026-11-01",
    "assigned_to": 2
  }'
```

### Response — 201 Created

```json
{
    "success": true,
    "message": "Task created successfully.",
    "data": {
        "id": 1,
        "company_id": 1,
        "title": "Redesign website",
        "description": "New landing page",
        "status": "pending",
        "priority": "high",
        "due_date": "2026-11-01",
        "completed_at": null,
        "assigned_to": 2,
        "assigned_user": {
            "id": 2,
            "name": "Adam",
            "email": "adam@company-a.test"
        },
        "created_at": "2026-10-07T10:00:00+00:00",
        "updated_at": "2026-10-07T10:00:00+00:00"
    }
}
```

### Default Values

If omitted:

```text
status   → pending
priority → medium
```

The `company_id` is always taken from the authenticated user.

The client cannot provide or override `company_id`.

---

# List Tasks

```bash
curl "http://localhost:8000/api/tasks?search=website&status=in_progress&priority=high&sort=due_date&per_page=10" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

### Query Parameters

| Parameter   | Description                                    |
| ----------- | ---------------------------------------------- |
| `search`    | Partial match against task title               |
| `status`    | `pending`, `in_progress`, `completed`          |
| `priority`  | `low`, `medium`, `high`                        |
| `sort`      | `created_at`, `due_date`, `priority`, `status` |
| `direction` | `asc` or `desc`                                |
| `per_page`  | Number of results per page, from 1 to 100      |
| `page`      | Page number                                    |

### Defaults

```text
per_page  → 15
direction → asc when sorting is specified
direction → newest first otherwise
```

### Response — 200 OK

```json
{
    "success": true,
    "message": "Tasks retrieved successfully.",
    "data": [
        {
            "id": 1,
            "company_id": 1,
            "title": "Redesign website",
            "description": "New landing page",
            "status": "in_progress",
            "priority": "high",
            "due_date": "2026-11-01",
            "completed_at": null,
            "assigned_to": 2,
            "assigned_user": {
                "id": 2,
                "name": "Adam",
                "email": "adam@company-a.test"
            },
            "created_at": "2026-10-07T10:00:00+00:00",
            "updated_at": "2026-10-07T10:00:00+00:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

---

# Show Task

```http
GET /api/tasks/{id}
```

The task must belong to the authenticated user's company.

If the task belongs to another company, the API returns:

```http
404 Not Found
```

This prevents exposing the existence of resources belonging to another tenant.

---

# Update Task

```bash
curl -X PATCH http://localhost:8000/api/tasks/1 \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"status":"completed"}'
```

### Response — 200 OK

```json
{
    "success": true,
    "message": "Task updated successfully.",
    "data": {
        "...": "updated task"
    }
}
```

When a task becomes `completed`, `completed_at` is automatically set by the server.

The client cannot control `completed_at`.

---

# Delete Task

```bash
curl -X DELETE http://localhost:8000/api/tasks/1 \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

### Response

```http
204 No Content
```

The response body is empty.

---

# Validation

Validation errors use the following structure:

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "status": [
            "The selected status is invalid."
        ],
        "assigned_to": [
            "The assigned user must belong to your company."
        ]
    }
}
```

HTTP status:

```http
422 Unprocessable Entity
```

---

# Multi-Tenant Architecture

The API strictly isolates data between companies.

### Tenant Identification

The tenant is determined from the authenticated user:

```php
$request->user()->company_id
```

The API never trusts `company_id` from the request body.

### Task Isolation

Every task operation is scoped to the authenticated user's company:

```php
where('company_id', $user->company_id)
```

This applies to:

* Listing tasks
* Showing a task
* Updating a task
* Deleting a task

### Cross-Tenant Access

If a user attempts to access a task belonging to another company:

```http
404 Not Found
```

Response:

```json
{
    "success": false,
    "message": "Resource not found."
}
```

This prevents leaking information about resources belonging to other tenants.

---

# Task Assignment

The `assigned_to` field is optional.

A task can be unassigned by sending:

```json
{
    "assigned_to": null
}
```

When assigning a task, the selected user must belong to the authenticated user's company.

For example:

```text
Company A user → can assign tasks only to Company A users
Company B user → can assign tasks only to Company B users
```

Assigning a user from another company returns:

```http
422 Unprocessable Entity
```

---

# Business Rules

## Task Completion

When a task changes to:

```text
completed
```

the server automatically sets:

```text
completed_at = current timestamp
```

Any `completed_at` value sent by the client is ignored.

---

## Reopening Completed Tasks

A completed task can only be reopened by:

* `admin`
* `manager`

Reopening means changing the status from:

```text
completed
```

to:

```text
pending
```

or:

```text
in_progress
```

If a normal `user` attempts this operation:

```http
403 Forbidden
```

Response:

```json
{
    "success": false,
    "message": "Only a manager or admin can reopen a completed task."
}
```

---

## Completed Timestamp

When a completed task is reopened:

```text
completed_at = null
```

Other fields of a completed task can still be edited as long as the status remains:

```text
completed
```

---

# User Roles

The system supports three roles:

| Role      | Description                     |
| --------- | ------------------------------- |
| `user`    | Standard user                   |
| `manager` | Can reopen completed tasks      |
| `admin`   | Full task management privileges |

The default role is:

```text
user
```

---

# Error Responses

All API errors follow a consistent JSON structure.

| Status | Case                                              | Example                                                |
| ------ | ------------------------------------------------- | ------------------------------------------------------ |
| `401`  | Missing/invalid token                             | `Unauthenticated.`                                     |
| `401`  | Invalid login credentials                         | `Invalid credentials.`                                 |
| `403`  | User not allowed to reopen task                   | `Only a manager or admin can reopen a completed task.` |
| `404`  | Task does not exist or belongs to another company | `Resource not found.`                                  |
| `422`  | Validation failure                                | `Validation failed.`                                   |
| `204`  | Successful deletion                               | Empty response                                         |

Example:

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

---

# Architectural Decisions

The project follows a clean and simple Laravel architecture.

## Controllers

Controllers are kept thin and are responsible mainly for:

* Receiving requests
* Calling the service layer
* Returning API responses

---

## Form Requests

Form Requests handle:

* Validation
* Input rules
* Authorization-related request validation

---

## API Resources

API Resources are responsible for transforming task data into a consistent JSON structure.

---

## Task Service

The `TaskService` contains the main business logic, including:

* Tenant-scoped task queries
* Filtering
* Searching
* Sorting
* Pagination
* Task creation
* Task updates
* Completion logic
* Reopen authorization rules

This keeps the controllers clean and easier to maintain.

---

## Authorization

Authorization is handled directly through:

```php
$user->company_id
```

and:

```php
$user->role
```

The project intentionally does not use Policies or Gates.

---

# Security Considerations

The API follows several important security principles:

### Tenant Isolation

Users cannot access tasks outside their company.

### No Client-Controlled Tenant ID

`company_id` is never accepted from the request.

### Secure Task Assignment

`assigned_to` is restricted to users belonging to the same company.

### Protected Routes

Task endpoints require:

```text
auth:sanctum
```

### Fixed Sorting Fields

The API only allows predefined sorting fields:

```text
created_at
due_date
priority
status
```

User input is never directly used as a database column name.

### Consistent Error Responses

API errors use a consistent JSON structure, while internal exception details are hidden when:

```env
APP_DEBUG=false
```

---

# Assumptions

* Users are created by an administrator.
* There is no public registration endpoint.
* Tokens are created on every successful login.
* Tokens do not expire unless `SANCTUM_TOKEN_EXPIRATION` is configured.
* `assigned_to` is optional.
* `assigned_to: null` removes the assignment.
* `PUT` requires `title`; all other fields are optional.
* `PATCH` accepts any subset of allowed fields.
* Sorting by `priority` and `status` follows the MySQL `ENUM` ordering.

### ENUM Ordering

Priority:

```text
low < medium < high
```

Status:

```text
pending < in_progress < completed
```

---

# Project Structure

The main architecture is organized around standard Laravel components:

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   ├── Requests/
│   └── Resources/
│
├── Models/
│   ├── Company.php
│   ├── Task.php
│   └── User.php
│
└── Services/
    └── TaskService.php

database/
└── migrations/

routes/
└── api.php

bootstrap/
└── app.php
```

---

# API Flow

The general request flow is:

```text
Client
   ↓
API Route
   ↓
Sanctum Authentication
   ↓
Form Request Validation
   ↓
Controller
   ↓
TaskService
   ↓
Eloquent / MySQL
   ↓
API Resource
   ↓
JSON Response
```

---

# Example Multi-Tenant Scenario

### Company A

```text
Alice - admin
Adam  - user
```

### Company B

```text
Bella - manager
Ben   - user
```

Alice can access:

```text
Company A tasks
```

but cannot access:

```text
Company B tasks
```

Likewise, Bella can only access:

```text
Company B tasks
```

This isolation is enforced server-side and cannot be bypassed by sending a different `company_id` in the request.

---

# License

This project was created as a Laravel backend/API assignment demonstrating:

* REST API development
* Laravel Sanctum authentication
* Multi-tenancy
* Eloquent relationships
* Request validation
* Service Layer architecture
* API Resources
* Pagination
* Filtering and sorting
* Role-based business rules
* Secure tenant isolation
