<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TaskService
{
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'created_at';

        if (! in_array($sort, Task::SORTABLE_FIELDS, true)) {
            $sort = 'created_at';
        }

        $direction = ($filters['direction'] ?? (isset($filters['sort']) ? 'asc' : 'desc')) === 'desc' ? 'desc' : 'asc';

        return Task::query()
            ->with('assignedUser')
            ->where('company_id', $user->company_id)
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->orderBy($sort, $direction)
            ->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 15);
    }

    public function find(User $user, string|int $id): Task
    {
        return Task::with('assignedUser')
            ->where('company_id', $user->company_id)
            ->findOrFail($id);
    }

    public function create(User $user, array $data): Task
    {
        $task = new Task($data);
        $task->company_id = $user->company_id;

        if ($task->status === Task::STATUS_COMPLETED) {
            $task->completed_at = now();
        }

        $task->save();

        return $task->load('assignedUser');
    }

    public function update(User $user, Task $task, array $data): Task
    {
        $newStatus = $data['status'] ?? $task->status;

        if ($task->status === Task::STATUS_COMPLETED && $newStatus !== Task::STATUS_COMPLETED) {
            if (! $user->canReopenTasks()) {
                throw new AccessDeniedHttpException('Only a manager or admin can reopen a completed task.');
            }

            $task->completed_at = null;
        }

        if ($task->status !== Task::STATUS_COMPLETED && $newStatus === Task::STATUS_COMPLETED) {
            $task->completed_at = now();
        }

        $task->fill($data)->save();

        return $task->load('assignedUser');
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
