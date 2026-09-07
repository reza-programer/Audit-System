<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Store;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $exportService
    ) {}

    public function index(Request $request): Response
    {
        $bySeverity = [
            'CRITICAL'    => Finding::where('severity', 'CRITICAL')->count(),
            'MAJOR'       => Finding::where('severity', 'MAJOR')->count(),
            'MINOR'       => Finding::where('severity', 'MINOR')->count(),
            'OBSERVATION' => Finding::where('severity', 'OBSERVATION')->count(),
        ];

        $byStatus = [
            'OPEN'                 => Finding::where('status', Finding::STATUS_OPEN)->count(),
            'IN_PROGRESS'          => Finding::where('status', Finding::STATUS_IN_PROGRESS)->count(),
            'WAITING_VERIFICATION' => Finding::where('status', Finding::STATUS_WAITING_VERIFICATION)->count(),
            'VERIFIED'             => Finding::where('status', Finding::STATUS_VERIFIED)->count(),
            'CLOSED'               => Finding::where('status', Finding::STATUS_CLOSED)->count(),
        ];

        $byCategory = \App\Models\AuditCategory::active()
            ->withCount('findings')
            ->get()
            ->map(fn ($cat) => [
                'id'         => $cat->id,
                'name'       => $cat->name,
                'count'      => $cat->findings_count,
                'total_loss' => (float) $cat->findings()->sum('loss_amount'),
            ]);

        $storeLosses = Store::withCount('audits')
            ->get()
            ->map(function ($s) {
                $totalLoss = Finding::whereHas('audit', fn ($q) => $q->where('store_id', $s->id))->sum('loss_amount');
                $totalFindings = Finding::whereHas('audit', fn ($q) => $q->where('store_id', $s->id))->count();
                return [
                    'store_code'     => $s->code,
                    'store_name'     => $s->name,
                    'area'           => $s->area,
                    'total_audits'   => $s->audits_count,
                    'total_findings' => $totalFindings,
                    'total_loss'     => (float) $totalLoss,
                ];
            })
            ->sortByDesc('total_loss')
            ->values();

        $totalFindings = Finding::count();
        $closedFindings = Finding::where('status', Finding::STATUS_CLOSED)->count();
        $closedOnTime = Finding::closedOnTime()->count();
        $closedOverdue = Finding::closedOverdue()->count();
        $completionRate = $totalFindings > 0 ? round(($closedFindings / $totalFindings) * 100, 1) : 0;

        return Inertia::render('Coordinator/Reports/Index', [
            'by_severity'     => $bySeverity,
            'by_status'       => $byStatus,
            'by_category'     => $byCategory,
            'store_losses'    => $storeLosses,
            'total_loss'      => (float) Finding::sum('loss_amount'),
            'total_findings'  => $totalFindings,
            'completion_rate' => $completionRate,
            'closed_on_time'  => $closedOnTime,
            'closed_overdue'  => $closedOverdue,
        ]);
    }

    public function exportFindings(): StreamedResponse
    {
        return $this->exportService->exportFindings();
    }

    public function exportStores(): StreamedResponse
    {
        return $this->exportService->exportStores();
    }

    public function exportSummary(): StreamedResponse
    {
        return $this->exportService->exportSummary();
    }
}
