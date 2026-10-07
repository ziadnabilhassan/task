<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(private TaskService $tasks)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'priority' => ['nullable', Rule::in(Task::PRIORITIES)],
            'sort' => ['nullable', Rule::in(Task::SORTABLE_FIELDS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tasks = $this->tasks->paginate($request->user(), $filters);

        return $this->success('Tasks retrieved successfully.', TaskResource::collection($tasks), 200, [
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($request->user(), $request->validated());

        return $this->success('Task created successfully.', new TaskResource($task), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $task = $this->tasks->find($request->user(), $id);

        return $this->success('Task retrieved successfully.', new TaskResource($task));
    }

    public function update(UpdateTaskRequest $request, string $id): JsonResponse
    {
        $task = $this->tasks->find($request->user(), $id);

        $task = $this->tasks->update($request->user(), $task, $request->validated());

        return $this->success('Task updated successfully.', new TaskResource($task));
    }

  public function destroy(Request $request, string $id): JsonResponse
{
    $task = $this->tasks->find($request->user(), $id);

    $this->tasks->delete($task);

    return response()->json([
        'message' => 'Task deleted successfully.',
    ], 200);
}
}
