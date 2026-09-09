<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Finding;
use App\Models\QualityFinding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QualityFindingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = QualityFinding::with(['finding.category', 'finding.sop', 'audit.store', 'reporter'])
            ->orderBy('created_at', 'desc');

        if ($user->isAuditor()) {
            $query->whereHas('audit', function ($q) use ($user) {
                $q->forAuditor($user->id);
            });
        }

        if ($request->filled('category')) {
            $cat = $request->query('category');
            $query->where(function ($q) use ($cat) {
                $q->where('quality_category', 'like', '%"' . $cat . '"%')
                  ->orWhere('quality_category', 'like', '%' . $cat . '%');
            });
        }

        $qualityFindings = $query->get()->map(fn ($qf) => [
            'id'                      => $qf->id,
            'quality_category'        => $qf->quality_categories[0] ?? $qf->quality_category,
            'quality_categories'      => $qf->quality_categories,
            'quality_categories_info' => $qf->quality_categories_info,
            'title'                   => $qf->title,
            'impact_amount'           => (float) $qf->impact_amount,
            'root_cause'              => $qf->root_cause,
            'systemic_issue'          => $qf->systemic_issue,
            'recommendation'          => $qf->recommendation,
            'auditor_notes'           => $qf->auditor_notes,
            'status'                  => $qf->status,
            'reported_by'             => $qf->reporter->name,
            'created_at'              => $qf->created_at->format('d M Y H:i'),
            'finding'                 => [
                'id'          => $qf->finding->id,
                'category'    => $qf->finding->category->name,
                'finding'     => $qf->finding->finding,
                'severity'    => $qf->finding->severity,
                'status'      => $qf->finding->status,
                'loss_amount' => (float) $qf->finding->loss_amount,
            ],
            'audit'            => [
                'id'           => $qf->audit->id,
                'audit_number' => $qf->audit->audit_number,
                'store_name'   => $qf->audit->store->name,
                'store_code'   => $qf->audit->store->code,
                'audit_date'   => $qf->audit->audit_date->format('d M Y'),
            ],
        ]);

        $stats = [
            'total'          => QualityFinding::count(),
            'impact_50m'     => QualityFinding::where(fn($q) => $q->where('quality_category', 'like', '%"impact_50m"%')->orWhere('quality_category', 'like', '%impact_50m%'))->count(),
            'fraud_risk'     => QualityFinding::where(fn($q) => $q->where('quality_category', 'like', '%"fraud_risk"%')->orWhere('quality_category', 'like', '%fraud_risk%'))->count(),
            'system_control' => QualityFinding::where(fn($q) => $q->where('quality_category', 'like', '%"system_control"%')->orWhere('quality_category', 'like', '%system_control%'))->count(),
            'org_structure'  => QualityFinding::where(fn($q) => $q->where('quality_category', 'like', '%"org_structure"%')->orWhere('quality_category', 'like', '%org_structure%'))->count(),
            'total_impact'   => (float) QualityFinding::sum('impact_amount'),
        ];

        return Inertia::render('Auditor/FindingQualities/Index', [
            'qualityFindings' => $qualityFindings,
            'stats'           => $stats,
            'categories'      => QualityFinding::categories(),
            'selectedCategory'=> $request->query('category', ''),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        // Get findings from audits assigned to this auditor (or all if admin/coordinator)
        $findingsQuery = Finding::with(['audit.store', 'category'])
            ->whereNotIn('status', [Finding::STATUS_CLOSED])
            ->whereDoesntHave('qualityReport')
            ->orderBy('id', 'desc');

        if ($user->isAuditor()) {
            $findingsQuery->whereHas('audit', function ($q) use ($user) {
                $q->forAuditor($user->id);
            });
        }

        $findings = $findingsQuery->get()->map(fn ($f) => [
            'id'           => $f->id,
            'audit_id'     => $f->audit_id,
            'audit_number' => $f->audit->audit_number,
            'store_name'   => $f->audit->store->name,
            'store_code'   => $f->audit->store->code,
            'category'     => $f->category->name,
            'finding'      => $f->finding,
            'severity'     => $f->severity,
            'loss_amount'  => (float) $f->loss_amount,
            'display_label'=> "[{$f->audit->audit_number}] {$f->audit->store->name} - #{$f->id} {$f->category->name} ({$f->finding})",
        ]);

        return Inertia::render('Auditor/FindingQualities/Create', [
            'findings'         => $findings,
            'categories'       => QualityFinding::categories(),
            'preselectedAudit' => $request->query('audit_id'),
            'preselectedFinding' => $request->query('finding_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Support both quality_categories (array) and legacy quality_category (string or array)
        $categoriesInput = $request->input('quality_categories', $request->input('quality_category'));
        if (is_string($categoriesInput)) {
            $categoriesInput = [$categoriesInput];
        }
        $request->merge(['quality_categories' => $categoriesInput]);

        $validated = $request->validate([
            'finding_id'           => 'required|exists:findings,id|unique:quality_findings,finding_id',
            'quality_categories'   => 'required|array|min:1',
            'quality_categories.*' => 'in:impact_50m,fraud_risk,system_control,org_structure',
            'title'                => 'required|string|max:255',
            'impact_amount'        => 'nullable|numeric|min:0',
            'root_cause'           => 'required|string',
            'systemic_issue'       => 'nullable|string',
            'recommendation'       => 'required|string',
            'auditor_notes'        => 'nullable|string',
        ], [
            'quality_categories.required' => 'Pilih minimal satu kategori target Finding Quality.',
            'quality_categories.min'      => 'Pilih minimal satu kategori target Finding Quality.',
        ]);

        $finding = Finding::findOrFail($validated['finding_id']);

        $qualityFinding = QualityFinding::create([
            'finding_id'       => $validated['finding_id'],
            'audit_id'         => $finding->audit_id,
            'quality_category' => $validated['quality_categories'],
            'title'            => $validated['title'],
            'impact_amount'    => $validated['impact_amount'] ?? null,
            'root_cause'       => $validated['root_cause'],
            'systemic_issue'   => $validated['systemic_issue'] ?? null,
            'recommendation'   => $validated['recommendation'],
            'auditor_notes'    => $validated['auditor_notes'] ?? null,
            'reported_by'      => $request->user()->id,
            'status'           => 'REPORTED',
        ]);

        return redirect()->route('auditor.finding-qualities.show', $qualityFinding)
            ->with('success', 'Data saved! Report Finding Quality berhasil dibuat.');
    }

    public function show(QualityFinding $findingQuality): Response
    {
        $findingQuality->load([
            'finding.category',
            'finding.sop',
            'finding.actionPlan',
            'audit.store',
            'audit.auditor',
            'reporter',
        ]);

        return Inertia::render('Auditor/FindingQualities/Show', [
            'qualityFinding' => [
                'id'                      => $findingQuality->id,
                'quality_category'        => $findingQuality->quality_categories[0] ?? '',
                'quality_categories'      => $findingQuality->quality_categories,
                'quality_categories_info' => $findingQuality->quality_categories_info,
                'title'                   => $findingQuality->title,
                'impact_amount'           => (float) $findingQuality->impact_amount,
                'root_cause'              => $findingQuality->root_cause,
                'systemic_issue'          => $findingQuality->systemic_issue,
                'recommendation'          => $findingQuality->recommendation,
                'auditor_notes'           => $findingQuality->auditor_notes,
                'status'                  => $findingQuality->status,
                'reported_by'             => $findingQuality->reporter->name,
                'created_at'              => $findingQuality->created_at->format('d M Y H:i'),
                'categories_info'         => QualityFinding::categories()[$findingQuality->quality_categories[0] ?? ''] ?? [],
                'finding'          => [
                    'id'               => $findingQuality->finding->id,
                    'category'         => $findingQuality->finding->category->name,
                    'sop'              => $findingQuality->finding->sop ? "{$findingQuality->finding->sop->code} - {$findingQuality->finding->sop->title}" : '-',
                    'finding'          => $findingQuality->finding->finding,
                    'severity'         => $findingQuality->finding->severity,
                    'status'           => $findingQuality->finding->status,
                    'loss_amount'      => (float) $findingQuality->finding->loss_amount,
                    'auditor_opinion'  => $findingQuality->finding->auditor_opinion,
                    'recommendation'   => $findingQuality->finding->recommendation,
                ],
                'audit'            => [
                    'id'           => $findingQuality->audit->id,
                    'audit_number' => $findingQuality->audit->audit_number,
                    'store_name'   => $findingQuality->audit->store->name,
                    'store_code'   => $findingQuality->audit->store->code,
                    'store_area'   => $findingQuality->audit->store->area,
                    'audit_date'   => $findingQuality->audit->audit_date->format('d M Y'),
                    'lead_auditor' => $findingQuality->audit->auditor->name,
                ],
            ],
            'categories' => QualityFinding::categories(),
        ]);
    }

    public function destroy(QualityFinding $findingQuality): RedirectResponse
    {
        $findingQuality->delete();

        return redirect()->route('auditor.finding-qualities.index')
            ->with('success', 'Report Finding Quality berhasil dihapus.');
    }
}
