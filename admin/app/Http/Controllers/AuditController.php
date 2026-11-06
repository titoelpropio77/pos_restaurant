<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('audit_logs', [
            'records' => AuditLog::with('admin')
                ->when($request->filled('action'), fn ($q) => $q->where('action', $request->query('action')))
                ->when($request->filled('entity'), fn ($q) => $q->where('entity', $request->query('entity')))
                ->when($request->filled('q'), fn ($q) => $q->where('description', 'ilike', '%'.$request->query('q').'%'))
                ->latest('id')
                ->paginate(40)
                ->withQueryString(),
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'),
            'entities' => AuditLog::distinct()->orderBy('entity')->pluck('entity'),
        ]);
    }
}
