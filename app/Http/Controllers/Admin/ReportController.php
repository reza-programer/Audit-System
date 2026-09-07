<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\Store;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $exportService
    ) {}

    public function index(Request $request): Response
    {
        // 1. Finding counts by Severity
        $bySeverity = [
            'CRITICAL'    => Finding::where('severity', 'CRITICAL')->count(),
            'MAJOR'       => Finding::where('severity', 'MAJOR')->count(),
            'MINOR'       => Finding::where('severity', 'MINOR')->count(),
            'OBSERVATION' => Finding::where('severity', 'OBSERVATION')->count(),
        ];

        // 2. Finding counts by Category
        $byCategory = AuditCategory::withCount('findings')
            ->get()
            ->map(fn ($cat) => [
                'name'  => $cat->name,
                'count' => $cat->findings_count,
            ]);

        // 3. Loss amount by Store
        $storeLosses = Store::with(['audits.findings'])
            ->get()
            ->map(function ($store) {
                $totalLoss = $store->audits->flatMap->findings->sum('loss_amount');
                $totalFindings = $store->audits->flatMap->findings->count();
                return [
                    'store_code'     => $store->code,
                    'store_name'     => $store->name,
                    'area'           => $store->area,
                    'total_audits'   => $store->audits->count(),
                    'total_findings' => $totalFindings,
                    'total_loss'     => $totalLoss,
                ];
            })
            ->sortByDesc('total_loss')
            ->values();

        // 4. Overall status breakdown
        $byStatus = [
            'OPEN'                 => Finding::where('status', 'OPEN')->count(),
            'IN_PROGRESS'          => Finding::where('status', 'IN_PROGRESS')->count(),
            'WAITING_VERIFICATION' => Finding::where('status', 'WAITING_VERIFICATION')->count(),
            'VERIFIED'             => Finding::where('status', 'VERIFIED')->count(),
            'CLOSED'               => Finding::where('status', 'CLOSED')->count(),
        ];

        $totalFindings = Finding::count();
        $closedFindings = Finding::where('status', 'CLOSED')->count();
        $closedOnTime = Finding::closedOnTime()->count();
        $closedOverdue = Finding::closedOverdue()->count();
        $completionRate = $totalFindings > 0 ? round(($closedFindings / $totalFindings) * 100, 1) : 0;

        return Inertia::render('Admin/Reports/Index', [
            'by_severity'     => $bySeverity,
            'by_category'     => $byCategory,
            'store_losses'    => $storeLosses,
            'by_status'       => $byStatus,
            'total_loss'      => (float) (Finding::sum('loss_amount') ?? 0),
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
