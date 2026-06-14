<?php

namespace App\Http\Middleware;

use App\Models\DownloadRequest;
use App\Models\Message;
use App\Support\Rbac;
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
        $user = $request->user();
        $roleKey = $user?->roleKey();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $roleKey,
                    'roleLabel' => $roleKey ? (config('rbac.roles')[$roleKey] ?? $roleKey) : null,
                    'initials' => $this->initials($user->name),
                ] : null,
                'can' => $user ? Rbac::capsFor($user) : [],
            ],
            'nav' => $user ? Rbac::modulesFor($roleKey) : [],
            'badges' => $user ? [
                'messages' => Message::where('recipient_id', $user->id)->whereNull('read_at')->count(),
                // Admins see pending approvals; requesters see their ready-to-download files.
                'downloads' => $user->can('download.approve')
                    ? DownloadRequest::where('status', 'pending')->count()
                    : DownloadRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
            ] : [],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'branding' => [
                'color' => rescue(fn () => \App\Models\Setting::brandColor(), '', false),
            ],
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}
