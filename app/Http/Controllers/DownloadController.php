<?php

namespace App\Http\Controllers;

use App\Models\DownloadRequest;
use App\Support\ReportCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    private const APPROVED_TTL_HOURS = 24;

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $canApprove = $user->can('download.approve');

        $map = fn (DownloadRequest $d) => [
            'id' => $d->id,
            'type' => $d->type,
            'label' => $d->label(),
            'format' => ReportCatalog::format($d->type),
            'summary' => ReportCatalog::describe($d->params),
            'status' => $d->status,
            'reason' => $d->reason,
            'requester' => $d->user?->name,
            'reviewer' => $d->reviewer?->name,
            'downloadable' => $d->isDownloadable(),
            'expired' => $d->isExpired(),
            'requested_at' => $d->created_at?->diffForHumans(),
            'requested_at_full' => $d->created_at?->toDayDateTimeString(),
        ];

        $mine = DownloadRequest::with('reviewer')->where('user_id', $user->id)
            ->latest('id')->limit(40)->get()->map($map);

        $pending = collect();
        $reviewed = collect();
        if ($canApprove) {
            $pending = DownloadRequest::with('user')->where('status', 'pending')
                ->latest('id')->get()->map($map);
            $reviewed = DownloadRequest::with(['user', 'reviewer'])->whereIn('status', ['approved', 'rejected', 'downloaded'])
                ->latest('reviewed_at')->limit(40)->get()->map($map);
        }

        return Inertia::render('Downloads/Index', [
            'canApprove' => $canApprove,
            'mine' => $mine,
            'pending' => $pending,
            'reviewed' => $reviewed,
        ]);
    }

    /** A staff member files a request to export a report. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('download.request') || $user->can('download.approve'), 403);

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(ReportCatalog::TYPES))],
            'params' => ['nullable', 'array'],
        ]);

        $def = ReportCatalog::get($data['type']);
        // Must hold the capability that the underlying report requires.
        abort_unless($user->can($def['cap']), 403, 'You are not allowed to request this report.');

        // Admins (approvers) get an auto-approved request they can download right away.
        $auto = $user->can('download.approve');

        $dr = DownloadRequest::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'params' => array_filter($data['params'] ?? [], fn ($v) => $v !== null && $v !== ''),
            'status' => $auto ? 'approved' : 'pending',
            'reviewed_by' => $auto ? $user->id : null,
            'reviewed_at' => $auto ? now() : null,
            'expires_at' => $auto ? now()->addHours(self::APPROVED_TTL_HOURS) : null,
        ]);

        return back()->with('success', $auto
            ? 'Report ready — open it from Downloads.'
            : 'Export requested. An administrator will review it shortly.');
    }

    public function approve(Request $request, DownloadRequest $download): RedirectResponse
    {
        abort_unless($request->user()->can('download.approve'), 403);

        if ($download->status === 'pending') {
            $download->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'expires_at' => now()->addHours(self::APPROVED_TTL_HOURS),
            ]);
        }

        return back()->with('success', 'Export approved.');
    }

    public function reject(Request $request, DownloadRequest $download): RedirectResponse
    {
        abort_unless($request->user()->can('download.approve'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        if ($download->status === 'pending') {
            $download->update([
                'status' => 'rejected',
                'reason' => $data['reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
        }

        return back()->with('success', 'Export rejected.');
    }

    /** Stream the approved file — generated fresh from live data. */
    public function file(Request $request, DownloadRequest $download): Response
    {
        $user = $request->user();
        abort_unless($download->user_id === $user->id || $user->can('download.approve'), 403);
        abort_unless($download->isDownloadable(), 403, 'This download is not approved or has expired.');

        $def = ReportCatalog::get($download->type);
        abort_if($def === null, 404);

        // Generate using the ORIGINAL report logic, in the requester's context so
        // capability checks and report attribution ("prepared by") are correct.
        $requester = $download->user;
        $synthetic = Request::create('/', 'GET', $download->params ?? []);
        $synthetic->setUserResolver(fn () => $requester);

        $response = app($def['controller'])->{$def['method']}($synthetic);

        if ($download->status !== 'downloaded') {
            $download->update(['status' => 'downloaded', 'downloaded_at' => now()]);
        }

        return $response instanceof Response ? $response : response($response);
    }
}
