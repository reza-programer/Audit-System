<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Finding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $assignedAudits = Audit::forAuditor($user->id)->count();

        $openFindings = Finding::whereHas('audit', fn ($q) => $q->where('auditor_id', $user->id))
            ->where('status', Finding::STATUS_OPEN)
            ->count();

        $inProgressFindings = Finding::whereHas('audit', fn ($q) => $q->where('auditor_id', $user->id))
            ->where('status', Finding::STATUS_IN_PROGRESS)
            ->count();

        $waitingVerification = Finding::whereHas('audit', fn ($q) => $q->where('auditor_id', $user->id))
            ->where('status', Finding::STATUS_WAITING_VERIFICATION)
            ->count();

        $auditorFindingsQuery = Finding::whereHas('audit', fn ($q) => $q->where('auditor_id', $user->id));

        $closedFindings = (clone $auditorFindingsQuery)
            ->where('status', Finding::STATUS_CLOSED)
            ->count();

        $closedOnTime = (clone $auditorFindingsQuery)
            ->closedOnTime()
            ->count();

        $closedOverdue = (clone $auditorFindingsQuery)
            ->closedOverdue()
            ->count();

        $overdueFindings = Finding::whereHas('audit', fn ($q) => $q->where('auditor_id', $user->id))
            ->overdue()
            ->count();

        $recentAudits = Audit::forAuditor($user->id)
            ->with(['store', 'findings'])
            ->orderBy('audit_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($audit) => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'store'        => $audit->store->name,
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'status'       => $audit->status,
                'findings_count' => $audit->findings->count(),
            ]);

        return Inertia::render('Auditor/Dashboard', [
            'stats' => [
                'assigned_audits'      => $assignedAudits,
                'open_findings'        => $openFindings,
                'in_progress_findings' => $inProgressFindings,
                'waiting_verification' => $waitingVerification,
                'closed_findings'      => $closedFindings,
                'closed_on_time'       => $closedOnTime,
                'closed_overdue'       => $closedOverdue,
                'overdue_findings'     => $overdueFindings,
            ],
            'recent_audits' => $recentAudits,
        ]);
    }
}
