<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Session flash data, shared on every Inertia response — this is
            // how ->with('success', '...') in TaskController reaches the Vue
            // side. There's no server-rendered flash partial like Twig's
            // session flashes; the frontend store (useFlashStore, see
            // LEARNING.md "Pinia vs Redux/Context") picks this prop up.
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }
}
