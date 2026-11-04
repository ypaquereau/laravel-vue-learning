<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent is Active Record: the model IS the query builder AND the row.
 * Doctrine keeps those separate (Entity + EntityManager/Repository), so there
 * is no Laravel equivalent of `$entityManager->persist($entity)` — you just
 * call `$task->save()` on the model itself. See LEARNING.md "Eloquent vs
 * Doctrine" for the fuller comparison (identity map, repositories, etc).
 */
#[Fillable(['title', 'description', 'completed'])]
class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    /**
     * Column => PHP type coercion, applied on both read and write.
     * Roughly analogous to a Doctrine column type, but declared on the model
     * rather than in mapping metadata.
     */
    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }

    /**
     * belongsTo ~ Doctrine's #[ManyToOne] side of the relationship: a Task
     * has exactly one owning User. Eloquent infers the foreign key
     * (user_id) from the method name unless told otherwise.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }
}
