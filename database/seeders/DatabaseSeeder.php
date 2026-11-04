<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Run with `sail artisan db:seed` —
     * the rough equivalent of a Symfony `doctrine:fixtures:load`.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $tasks = Task::factory(8)->for($user)->create();

        $work = Tag::factory()->for($user)->create(['name' => 'work', 'slug' => 'work']);
        $home = Tag::factory()->for($user)->create(['name' => 'home', 'slug' => 'home']);
        $urgent = Tag::factory()->for($user)->create(['name' => 'urgent', 'slug' => 'urgent']);

        $tasks[0]->tags()->attach([$work->id, $urgent->id]);
        $tasks[1]->tags()->attach([$home->id]);
        $tasks[2]->tags()->attach([$work->id]);
    }
}
