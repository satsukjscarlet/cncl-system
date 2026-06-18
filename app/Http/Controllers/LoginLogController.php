<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginLog::with('user');

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->keyword . '%')
                    ->orWhere('ip_address', 'like', '%' . $request->keyword . '%')
                    ->orWhere('user_agent', 'like', '%' . $request->keyword . '%')
                    ->orWhere('message', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('logged_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('logged_at', '<=', $request->date_to);
        }

        $logs = $query
            ->latest('logged_at')
            ->paginate(20)
            ->withQueryString();

        $users = User::orderBy('name')->get();

        return view('login_logs.index', compact('logs', 'users'));
    }
}