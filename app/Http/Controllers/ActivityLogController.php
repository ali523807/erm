<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'module' => ['nullable', 'string', 'max:80'],
            'action' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $logs = ActivityLog::with('user')
            ->when($validated['module'] ?? null, fn ($query, string $module) => $query->where('module', $module))
            ->when($validated['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($validated['user_id'] ?? null, fn ($query, string $userId) => $query->where('user_id', $userId))
            ->when($validated['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'modules' => ActivityLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::whereHas('companies', fn ($query) => $query->whereKey($request->user()->current_company_id))
                ->orderBy('name')
                ->get(),
            'filters' => $validated,
        ]);
    }
}
