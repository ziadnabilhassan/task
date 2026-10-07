# Multi-Tenant Task Management API

A RESTful JSON API for managing tasks in a multi-tenant setup. Every user belongs to a company, and a user can only see and manage tasks that belong to their own company. Authentication is handled by Laravel Sanctum (personal access tokens).

## Requirements

- PHP 8.2 or higher (with the `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json` extensions)
- Composer 2
- MySQL 8.0+ (or MariaDB 10.6+)
- Laravel 12 / Laravel Sanctum 4

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Environment configuration

Open `.env` and set the database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task
DB_USERNAME=root
DB_PASSWORD=
```

Optional: set `SANCTUM_TOKEN_EXPIRATION` (in minutes) to make tokens expire. By default tokens do not expire.

## Database setup

Create an empty database, then run the migrations:

```bash
mysql -u root -e "CREATE DATABASE task_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate
```

Migrations create the `companies`, `users` (with `company_id` and `role`), `tasks` and `personal_access_tokens` tables.

## Sanctum setup

Sanctum is already installed and configured:

- The `personal_access_tokens` migration is included in `database/migrations`.
- `App\Models\User` uses the `HasApiTokens` trait.
- `routes/api.php` is registered in `bootstrap/app.php`.
- Task routes are protected by the `auth:sanctum` middleware.

Clients authenticate with a Bearer token returned by `POST /api/login`.

## Running the project

```bash
php artisan serve
```

The API is available at `http://localhost:8000/api`.

### Creating the first companies and users

The project ships without seeders, so create your initial data with Tinker (`php artisan tinker`):

```php
$a = App\Models\Company::create(['name' => 'Company A']);
$b = App\Models\Company::create(['name' => 'Company B']);

App\Models\User::create(['company_id' => $a->id, 'name' => 'Alice', 'email' => 'alice@company-a.test', 'password' => 'secret123', 'role' => 'admin']);
App\Models\User::create(['company_id' => $a->id, 'name' => 'Adam', 'email' => 'adam@company-a.test', 'password' => 'secret123', 'role' => 'user']);
App\Models\User::create(['company_id' => $b->id, 'name' => 'Bella', 'email' => 'bella@company-b.test', 'password' => 'secret123', 'role' => 'manager']);
App\Models\User::create(['company_id' => $b->id, 'name' => 'Ben', 'email' => 'ben@company-b.test', 'password' => 'secret123', 'role' => 'user']);
```

Passwords are hashed automatically by the model.

## API endpoints

| Method    | Endpoint           | Auth | Description                       |
|-----------|--------------------|------|-----------------------------------|
| POST      | `/api/login`       | No   | Log in and receive a token        |
| GET       | `/api/tasks`       | Yes  | List the company's tasks          |
| POST      | `/api/tasks`       | Yes  | Create a task                     |
| GET       | `/api/tasks/{id}`  | Yes  | Show a task                       |
| PUT/PATCH | `/api/tasks/{id}`  | Yes  | Update a task                     |
| DELETE    | `/api/tasks/{id}`  | Yes  | Delete a task                     |

Send `Accept: application/json` and, for authenticated routes, `Authorization: Bearer <token>`.

## Authentication

### Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email": "alice@company-a.test", "password": "secret123"}'
```

Response `200`:

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

Wrong credentials return `401` with `{"success": false, "message": "Invalid credentials."}`.

## Example requests and responses

### Create a task

```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"title": "Redesign website", "description": "New landing page", "priority": "high", "due_date": "2026-11-01", "assigned_to": 2}'
```

Response `201`:

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

`status` defaults to `pending` and `priority` defaults to `medium` when omitted. `company_id` is never read from the request.

### List tasks

```bash
curl "http://localhost:8000/api/tasks?search=website&status=in_progress&priority=high&sort=due_date&per_page=10" \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

| Parameter   | Description                                                        |
|-------------|--------------------------------------------------------------------|
| `search`    | Partial match on the task title                                    |
| `status`    | `pending`, `in_progress` or `completed`                            |
| `priority`  | `low`, `medium` or `high`                                          |
| `sort`      | `created_at`, `due_date`, `priority` or `status`                   |
| `direction` | `asc` or `desc` (default: `asc` when `sort` is given, otherwise newest first) |
| `per_page`  | 1 to 100 (default 15)                                              |
| `page`      | Page number                                                        |

Response `200`:

```json
{
    "success": true,
    "message": "Tasks retrieved successfully.",
    "data": [
        { "id": 1, "title": "Redesign website", "status": "in_progress", "priority": "high" }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

(Each item in `data` contains the full task object shown above; it is shortened here.)

### Update a task

```bash
curl -X PATCH http://localhost:8000/api/tasks/1 \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer <token>" \
  -d '{"status": "completed"}'
```

Response `200` with `"message": "Task updated successfully."` and `completed_at` filled in.

### Delete a task

```bash
curl -X DELETE http://localhost:8000/api/tasks/1 \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

Response `204` with an empty body.

### Error responses

All errors use the same shape (`success: false` plus a `message`).

| Status | Case                                         | Example body                                                                   |
|--------|----------------------------------------------|--------------------------------------------------------------------------------|
| 401    | Missing or invalid token / wrong credentials | `{"success": false, "message": "Unauthenticated."}`                            |
| 403    | Normal user tries to reopen a completed task | `{"success": false, "message": "Only a manager or admin can reopen a completed task."}` |
| 404    | Task does not exist or belongs to another company | `{"success": false, "message": "Resource not found."}`                    |
| 422    | Validation failed                            | see below                                                                      |

Validation error (`422`):

```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "status": ["The selected status is invalid."],
        "assigned_to": ["The assigned user must belong to your company."]
    }
}
```

## Multi-tenant behavior

- The tenant is always taken from the authenticated user (`$request->user()->company_id`). The request body is never trusted for `company_id`; it is not even a fillable attribute on `Task`.
- Every task query (list, show, update, delete) is filtered with `where('company_id', <user's company>)`.
- A task from another company is reported as `404 Not Found`, so its existence is not revealed.
- `assigned_to` is validated with an `exists` rule that is limited to users of the authenticated user's company. Assigning to a user of another company returns `422`.

## Business rules

- When a task becomes `completed`, `completed_at` is set to the current time by the server. A `completed_at` value sent by the client is ignored.
- When a completed task is changed to `pending` or `in_progress`, `completed_at` is cleared.
- Only users with the role `manager` or `admin` can reopen a completed task. Other users receive `403 Forbidden`. Other fields of a completed task can still be edited as long as its status stays `completed`.
- User roles are `user` (default), `manager` and `admin`, stored in the `users.role` column.

## Assumptions

- Users are created by an administrator (for example with Tinker); there is no registration endpoint.
- `assigned_to` is optional; a task can be unassigned by sending `null`.
- `PUT` requires `title`; all other fields are optional on both `PUT` and `PATCH`. `PATCH` accepts any subset of fields.
- Tokens are created on every login and do not expire unless `SANCTUM_TOKEN_EXPIRATION` is set.
- Sorting by `priority` and `status` follows the order of the database enum values (`low < medium < high`, `pending < in_progress < completed`), which is how MySQL orders `ENUM` columns.
- `DELETE` returns `204` with no body, so it has no JSON envelope.

## Architectural decisions

- Plain Laravel structure: controllers, Form Requests, an API Resource and Eloquent models.
- A single `TaskService` holds the non-trivial task logic (tenant-scoped queries, filtering and sorting, the completed/reopen rules). Controllers stay thin.
- Authorization is done directly through the authenticated user's `company_id` and `role`, without policies or gates.
- Sort fields, status values and priority values are validated against fixed lists, so request input is never used directly as a column name.
- JSON error handling for `api/*` routes is configured in `bootstrap/app.php`, so every error has the same structure and internal exceptions are not exposed when `APP_DEBUG=false`.
#   t a s k  
 