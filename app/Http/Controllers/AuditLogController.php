<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends AppBaseController
{
    public function index()
    {
        return view('audit_logs.index');
    }

    /** History of one bill, receipt, visit... and of its lines. */
    public function history(string $type, int $id)
    {
        abort_unless(array_key_exists($type, AuditLog::HISTORY), 404);
        $logs = AuditLog::historyFor($type, $id);

        return view('audit_logs.history', compact('type', 'id', 'logs'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('audit_logs.show', compact('auditLog'));
    }
}
