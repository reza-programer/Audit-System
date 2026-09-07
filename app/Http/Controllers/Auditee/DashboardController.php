<?php

namespace App\Http\Controllers\Auditee;

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
        $user  = $request->user();
        $storeIds = $user->stores()->pluck('stores.id');

        $totalAudits = Audit::whereIn('store_id', $storeIds)->count();

        $openFindings = Finding::whereHas('audit', fn ($q) => $q->whereIn('store_id', $storeIds))
            ->where('status', Finding::STATUS_OPEN)
            ->count();

        $inProgressFindings = Finding::whereHas('audit', fn ($q) => $q->whereIn('store_id', $storeIds))
            ->where('status', Finding::STATUS_IN_PROGRESS)
            ->count();

        $waitingVerification = Finding::whereHas('audit', fn ($q) => $q->whereIn('store_id', $storeIds))
            ->where('status', Finding::STATUS_WAITING_VERIFICATION)
            ->count();

        $auditeeFindingsQuery = Finding::whereHas('audit', fn ($q) => $q->whereIn('store_id', $storeIds));

        $closedFindings = (clone $auditeeFindingsQuery)
            ->where('status', Finding::STATUS_CLOSED)
            ->count();

        $closedOnTime = (clone $auditeeFindingsQuery)
            ->closedOnTime()
            ->count();

        $closedOverdue = (clone $auditeeFindingsQuery)
            ->closedOverdue()
            ->count();

        $overdueActions = Finding::whereHas('audit', fn ($q) => $q->whereIn('store_id', $storeIds))
            ->overdue()
            ->count();

        $recentAudits = Audit::whereIn('store_id', $storeIds)
            ->with(['store', 'auditor'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'store'          => $audit->store->name,
                'auditor'        => $audit->auditor->name,
                'audit_date'     => $audit->audit_date->format('d M Y'),
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
            ]);

        return Inertia::render('Auditee/Dashboard', [
            'stats' => [
                'total_audits'         => $totalAudits,
                'open_findings'        => $openFindings,
                'in_progress_findings' => $inProgressFindings,
                'waiting_verification' => $waitingVerification,
                'closed_findings'      => $closedFindings,
                'closed_on_time'       => $closedOnTime,
                'closed_overdue'       => $closedOverdue,
                'overdue_actions'      => $overdueActions,
            ],
            'recent_audits' => $recentAudits,
        ]);
    }
}
