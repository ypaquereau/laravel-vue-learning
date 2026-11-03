<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A FormRequest is Laravel's analogue of a Symfony Form type bound to a
 * Validator: it bundles "who may do this" (authorize) and "what shape is
 * valid" (rules) into one class the controller type-hints instead of
 * injecting Request and calling the validator manually.
 */
class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may create a task for themselves; the
        // "may you touch THIS task" check lives in TaskPolicy instead,
        // the same split Symfony makes between a route's access_control
        // and a Voter.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'completed' => ['sometimes', 'boolean'],
        ];
    }
}
