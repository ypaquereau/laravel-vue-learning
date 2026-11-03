<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * A Policy is Laravel's Voter: one class per model, one method per action,
 * resolved automatically from the model's class name. `$user->can('update',
 * $task)` and Symfony's `$this->denyAccessUnlessGranted('EDIT', $task)` are
 * doing the same job — the difference is Laravel wires the Policy to the
 * Task model by naming convention, no explicit registration needed (unless
 * you break the convention, in which case see AppServiceProvider).
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
