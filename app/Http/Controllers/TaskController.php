<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Support\SyncTaskTags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        $tagFilter = $request->string('tag')->trim()->toString() ?: null;

        $tasks = $request->user()
            ->tasks()
            ->with('tags:id,name,slug')
            ->when($tagFilter, function ($query) use ($tagFilter) {
                $query->whereHas('tags', fn ($q) => $q->where('slug', Str::lower($tagFilter)));
            })
            ->latest()
            ->get();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'tag' => $tagFilter !== null ? Str::lower($tagFilter) : null,
            ],
            'availableTags' => $request->user()
                ->tags()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Tasks/Create', [
            'availableTags' => $request->user()
                ->tags()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $task = $request->user()->tasks()->create($validated);
        app(SyncTaskTags::class)($task, $tagNames);

        return redirect()->route('tasks.index')->with('success', 'Task created.');
    }

    public function edit(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);
        $task->load('tags:id,name,slug');

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'availableTags' => $request->user()
                ->tags()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * UpdateTaskRequest::authorize() already checked the policy before this
     * method runs — Laravel resolves and authorizes FormRequests during
     * route-model binding, before the controller method is even called.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();
        $tagNames = $validated['tags'] ?? [];
        unset($validated['tags']);

        $task->update($validated);
        app(SyncTaskTags::class)($task, $tagNames);

        return redirect()->route('tasks.index')->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task deleted.');
    }
}
