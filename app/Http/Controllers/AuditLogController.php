<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    /**
     * Display a listing of admin audit logs.
     */
    public function index(Request $request)
    {
        $logs = AuditLog::with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return Inertia::render('audit-logs/Index', [
            'logs' => $logs,
        ]);
    }
}
