<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->company_id === $task->company_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->company_id === $task->company_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->company_id === $task->company_id;
    }

    public function reopen(User $user, Task $task): bool
    {
        return $user->company_id === $task->company_id
            && $user->canReopenTasks();
    }
}
