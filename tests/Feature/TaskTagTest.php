<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_attached_to_user_owned_tags(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $tag = Tag::factory()->for($user)->create(['name' => 'work', 'slug' => 'work']);

        $task->tags()->attach($tag);

        $this->assertTrue($task->fresh()->tags->contains($tag));
        $this->assertTrue($user->fresh()->tags->contains($tag));
    }

    public function test_store_rejects_more_than_ten_tags(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Too many tags',
            'tags' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k'],
        ]);

        $response->assertSessionHasErrors('tags');
    }

    public function test_store_rejects_tag_longer_than_thirty_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Long tag',
            'tags' => [str_repeat('x', 31)],
        ]);

        $response->assertSessionHasErrors('tags.0');
    }

    public function test_store_creates_tags_and_attaches_them(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'title' => 'Tagged task',
            'description' => null,
            'tags' => ['Work', 'urgent'],
        ]);

        $response->assertRedirect(route('tasks.index'));

        $task = $user->tasks()->first();
        $this->assertNotNull($task);
        $this->assertEqualsCanonicalizing(
            ['urgent', 'work'],
            $task->tags()->pluck('slug')->all()
        );
        $this->assertSame(2, $user->tags()->count());
    }

    public function test_update_syncs_tags_and_reuses_existing_slug(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $existing = Tag::factory()->for($user)->create(['name' => 'home', 'slug' => 'home']);

        $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'completed' => false,
            'tags' => ['HOME', 'errands'],
        ])->assertRedirect(route('tasks.index'));

        $task->refresh();
        $this->assertEqualsCanonicalizing(
            ['home', 'errands'],
            $task->tags()->pluck('slug')->all()
        );
        $this->assertSame(1, Tag::query()->where('slug', 'home')->count());
        $this->assertTrue($task->tags->contains('id', $existing->id));
        $this->assertSame('home', $existing->fresh()->name);
    }

    public function test_update_with_empty_tags_clears_attachments_but_keeps_tag_rows(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();
        $tag = Tag::factory()->for($user)->create(['name' => 'work', 'slug' => 'work']);
        $task->tags()->attach($tag);

        $this->actingAs($user)->put(route('tasks.update', $task), [
            'title' => $task->title,
            'description' => $task->description,
            'completed' => false,
            'tags' => [],
        ])->assertRedirect(route('tasks.index'));

        $this->assertCount(0, $task->fresh()->tags);
        $this->assertTrue(Tag::query()->whereKey($tag->id)->exists());
    }

    public function test_index_filters_tasks_by_tag_slug(): void
    {
        $user = User::factory()->create();
        $work = Tag::factory()->for($user)->create(['name' => 'work', 'slug' => 'work']);
        $home = Tag::factory()->for($user)->create(['name' => 'home', 'slug' => 'home']);

        $workTask = Task::factory()->for($user)->create(['title' => 'Office']);
        $homeTask = Task::factory()->for($user)->create(['title' => 'Chores']);
        $workTask->tags()->attach($work);
        $homeTask->tags()->attach($home);

        $response = $this->actingAs($user)->get(route('tasks.index', ['tag' => 'work']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.title', 'Office')
            ->where('filters.tag', 'work'));
    }

    public function test_user_cannot_attach_another_users_tag_name_collision_across_accounts(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Tag::factory()->for($alice)->create(['name' => 'work', 'slug' => 'work']);

        $this->actingAs($bob)->post(route('tasks.store'), [
            'title' => 'Bob work',
            'tags' => ['work'],
        ])->assertRedirect(route('tasks.index'));

        $this->assertSame(1, $alice->tags()->count());
        $this->assertSame(1, $bob->tags()->count());
        $this->assertSame(2, Tag::query()->where('slug', 'work')->count());
    }
}
