<?php

namespace App\Http\Controllers\Coordinator;

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
        $totalAudits = Audit::count();
        $activeAudits = Audit::whereIn('status', ['PLANNED', 'IN_PROGRESS'])->count();
        $totalFindings = Finding::count();
        $openFindings = Finding::where('status', Finding::STATUS_OPEN)->count();
        $inProgressFindings = Finding::where('status', Finding::STATUS_IN_PROGRESS)->count();
        $waitingVerification = Finding::where('status', Finding::STATUS_WAITING_VERIFICATION)->count();
        $closedFindings = Finding::where('status', Finding::STATUS_CLOSED)->count();
        $closedOnTime = Finding::closedOnTime()->count();
        $closedOverdue = Finding::closedOverdue()->count();
        $overdueFindings = Finding::overdue()->count();
        $totalLossAmount = Finding::sum('loss_amount') ?? 0;

        $recentAudits = Audit::with(['store', 'auditor'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'store'          => $audit->store?->name ?? '—',
                'auditor'        => $audit->auditor?->name ?? '—',
                'audit_date'     => $audit->audit_date?->format('d M Y') ?? '—',
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
            ]);

        $recentFindings = Finding::with(['audit.store', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($f) => [
                'id'           => $f->id,
                'audit_number' => $f->audit?->audit_number ?? '—',
                'store'        => $f->audit?->store?->name ?? '—',
                'category'     => $f->category?->name ?? '—',
                'finding'      => \Str::limit($f->finding, 50),
                'loss_amount'  => $f->loss_amount,
                'severity'     => $f->severity,
                'status'       => $f->status,
            ]);

        return Inertia::render('Coordinator/Dashboard', [
            'stats' => [
                'total_audits'         => $totalAudits,
                'active_audits'        => $activeAudits,
                'total_findings'       => $totalFindings,
                'open_findings'        => $openFindings,
                'in_progress_findings' => $inProgressFindings,
                'waiting_verification' => $waitingVerification,
                'closed_findings'      => $closedFindings,
                'closed_on_time'       => $closedOnTime,
                'closed_overdue'       => $closedOverdue,
                'overdue_findings'     => $overdueFindings,
                'total_loss_amount'    => $totalLossAmount,
            ],
            'recent_audits'   => $recentAudits,
            'recent_findings' => $recentFindings,
        ]);
    }
}
