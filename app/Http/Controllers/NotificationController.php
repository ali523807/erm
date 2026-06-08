<?php

namespace App\Http\Controllers;

use App\Models\TenantNotification;
use App\Services\NotificationGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:unread,read'],
            'type' => ['nullable', 'string', 'max:80'],
        ]);

        $notifications = TenantNotification::query()
            ->where(function ($query) use ($request): void {
                $query
                    ->whereNull('user_id')
                    ->orWhere('user_id', $request->user()->id);
            })
            ->when(($validated['status'] ?? null) === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when(($validated['status'] ?? null) === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->orderByRaw('read_at IS NULL DESC')
            ->latest('due_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'types' => TenantNotification::query()->select('type')->distinct()->orderBy('type')->pluck('type'),
            'filters' => $validated,
        ]);
    }

    public function generate(Request $request, NotificationGenerator $generator): RedirectResponse
    {
        $count = $generator->generateForCompany($request->user()->currentCompany);

        return back()->with('status', "{$count} reminder notifications generated.");
    }

    public function markRead(TenantNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === null || (int) $notification->user_id === auth()->id(), 403);

        $notification->update(['read_at' => now()]);

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        TenantNotification::query()
            ->whereNull('read_at')
            ->where(function ($query) use ($request): void {
                $query
                    ->whereNull('user_id')
                    ->orWhere('user_id', $request->user()->id);
            })
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
