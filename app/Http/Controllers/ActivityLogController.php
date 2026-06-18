<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer');

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('log_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('description', 'like', '%' . $request->keyword . '%')
                    ->orWhere('subject_type', 'like', '%' . $request->keyword . '%')
                    ->orWhere('causer_type', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                ->where('causer_type', User::class);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $users = User::orderBy('name')->get();

        $logNames = Activity::select('log_name')
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return view('activity_logs.index', compact(
            'logs',
            'users',
            'logNames'
        ));
    }

    public function show(Activity $activityLog)
    {
        $activityLog->load('causer');

        return view('activity_logs.show', compact('activityLog'));
    }
}