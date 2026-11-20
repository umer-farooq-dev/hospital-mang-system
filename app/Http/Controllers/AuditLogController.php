<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends AppBaseController
{
    public function index()
    {
        return view('audit_logs.index');
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');

        return view('audit_logs.show', compact('auditLog'));
    }
}
