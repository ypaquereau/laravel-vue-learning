<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A Laravel resource controller is the Symfony equivalent of a controller
 * class with one action per CRUD operation, except Route::resource() (see
 * routes/web.php) wires all seven methods to their conventional routes in
 * one line instead of one #[Route] attribute per method.
 *
 * Each method returns Inertia::render(component, props) instead of
 * $this->render('template.twig', [...]) — same idea (server decides the
 * view, hands it data), but the "template" is a Vue single-file component
 * compiled by Vite, not a server-rendered Blade/Twig file.
 */
class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $tasks = $request->user()->tasks()->latest()->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tasks/Create');
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $request->user()->tasks()->create($request->validated());

        return redirect()->route('tasks.index')->with('success', 'Task created.');
    }

    public function edit(Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
        ]);
    }

    /**
     * UpdateTaskRequest::authorize() already checked the policy before this
     * method runs — Laravel resolves and authorizes FormRequests during
     * route-model binding, before the controller method is even called.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()->route('tasks.index')->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }
}
