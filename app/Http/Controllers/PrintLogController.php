<?php

namespace App\Http\Controllers;

use App\Models\PrintLog;
use App\Models\User;
use Illuminate\Http\Request;

class PrintLogController extends Controller
{
    public function index(Request $request)
    {
        $query = PrintLog::with([
            'certificate.request.customer',
            'certificate.request.distributionCenter',
            'user',
        ]);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('reason', 'like', '%' . $request->keyword . '%')
                    ->orWhereHas('certificate', function ($c) use ($request) {
                        $c->where('certificate_no', 'like', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('certificate.request', function ($r) use ($request) {
                        $r->where('request_no', 'like', '%' . $request->keyword . '%')
                            ->orWhere('invoice_no', 'like', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('certificate.request.customer', function ($c) use ($request) {
                        $c->where('customer_name', 'like', '%' . $request->keyword . '%')
                            ->orWhere('project_name', 'like', '%' . $request->keyword . '%');
                    });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $users = User::orderBy('name')->get();

        return view('print_logs.index', compact('logs', 'users'));
    }
}