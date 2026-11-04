<?php

namespace App\Support;

use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Str;

class SyncTaskTags
{
    /**
     * @param  list<string>  $tagNames
     */
    public function __invoke(Task $task, array $tagNames): void
    {
        $ids = collect($tagNames)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique(fn (string $name) => Str::lower($name))
            ->map(function (string $name) use ($task) {
                $slug = Str::lower($name);

                return Tag::query()->firstOrCreate(
                    [
                        'user_id' => $task->user_id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $name,
                    ],
                )->id;
            })
            ->all();

        $task->tags()->sync($ids);
    }
}
