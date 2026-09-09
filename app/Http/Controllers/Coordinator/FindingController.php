<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FindingController extends Controller
{
    public function index(Request $request): Response
    {
        $findings = Finding::with(['audit.store', 'audit.auditor', 'category', 'severityReviewer'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($f) => [
                'id'                   => $f->id,
                'audit_id'             => $f->audit_id,
                'audit_number'         => $f->audit?->audit_number ?? '—',
                'store'                => $f->audit?->store?->name ?? '—',
                'auditor'              => $f->audit?->auditor?->name ?? '—',
                'category'             => $f->category?->name ?? '—',
                'finding'              => $f->finding,
                'loss_amount'          => $f->loss_amount,
                'severity'             => $f->severity,
                'severity_status'      => $f->severity_status,
                'is_severity_locked'   => $f->is_severity_locked,
                'severity_reviewed_by' => $f->severityReviewer?->name,
                'status'               => $f->status,
                'created_at'           => $f->created_at->format('d M Y'),
            ]);

        return Inertia::render('Coordinator/Findings/Index', [
            'findings'   => $findings,
            'categories' => AuditCategory::active()->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Finding $finding): Response
    {
        $finding->load([
            'audit.store',
            'audit.documents',
            'audit.auditor',
            'category',
            'sop',
            'severityReviewer',
            'actionPlan',
            'followUp',
            'evidences.uploader',
            'evidences.verifier',
        ]);

        $docsCount = $finding->audit?->documents?->count() ?? 0;
        $hasDocs = $docsCount > 0 || $finding->evidences->isNotEmpty();
        $hasActionPlan = !empty($finding->actionPlan?->action_plan);

        return Inertia::render('Coordinator/Findings/Show', [
            'finding' => [
                'id'                   => $finding->id,
                'audit'                => array_merge(
                    $finding->audit?->only(['id', 'audit_number', 'status', 'audit_date']) ?? [],
                    ['documents_count' => $docsCount, 'has_documents' => $docsCount > 0]
                ),
                'has_documents'        => $hasDocs,
                'documents_count'      => $docsCount,
                'has_action_plan'      => $hasActionPlan,
                'store'                => $finding->audit?->store?->only(['name', 'code', 'area']) ?? [],
                'auditor'              => $finding->audit?->auditor?->only(['name', 'email', 'phone']) ?? [],
                'category'             => $finding->category?->name ?? '—',
                'sop'                  => $finding->sop?->only(['code', 'title']),
                'finding'              => $finding->finding,
                'loss_amount'          => $finding->loss_amount,
                'severity'             => $finding->severity,
                'severity_status'      => $finding->severity_status,
                'severity_reviewed_by' => $finding->severityReviewer?->name,
                'severity_reviewed_at' => $finding->severity_reviewed_at?->format('d M Y H:i'),
                'severity_notes'       => $finding->severity_notes,
                'is_severity_locked'   => $finding->is_severity_locked,
                'status'               => $finding->status,
                'auditor_opinion'      => $finding->auditor_opinion,
                'recommendation'       => $finding->recommendation,
                'action_plan'          => $finding->actionPlan,
                'follow_up'            => $finding->followUp,
                'evidences'            => $finding->evidences->map(fn ($e) => [
                    'id'                  => $e->id,
                    'description'         => $e->description,
                    'verification_status' => $e->verification_status,
                    'rejection_reason'    => $e->rejection_reason,
                    'uploaded_by'         => $e->uploader->name,
                    'verified_by'         => $e->verifier?->name,
                    'uploaded_at'         => $e->created_at->format('d M Y H:i'),
                    'file_url'            => $e->file_url,
                ]),
                'can_close' => $finding->isCloseable() && (bool) $request->user()?->can('close', $finding),
            ],
        ]);
    }

    public function close(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('close', $finding);

        $finding->update(['status' => Finding::STATUS_CLOSED]);

        // Update action plan status
        $finding->actionPlan?->update(['status' => 'COMPLETED']);

        WhatsAppService::notifyFindingClosed($finding);

        return redirect()->route('coordinator.findings.show', $finding)
            ->with('success', 'Data tersimpan! Temuan audit resmi ditutup (CLOSED).');
    }

    public function updateRecommendation(Request $request, Finding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'recommendation' => 'required|string|max:2000',
        ]);

        $finding->update([
            'recommendation' => $validated['recommendation'],
        ]);

        return back()->with('success', 'Rekomendasi perbaikan temuan audit berhasil diperbarui.');
    }

    public function reviewSeverity(Request $request, Finding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'severity'       => 'required|in:MINOR,MEDIUM,MAJOR,CRITICAL,OBSERVATION',
            'severity_notes' => 'nullable|string|max:500',
            'recommendation' => 'nullable|string|max:2000',
        ]);

        $oldSeverity = $finding->severity;
        $isAdjusted  = $oldSeverity !== $validated['severity'];

        $updateData = [
            'severity'             => $validated['severity'],
            'severity_status'      => $isAdjusted ? 'ADJUSTED' : 'APPROVED',
            'severity_reviewed_by' => $request->user()->id,
            'severity_reviewed_at' => now(),
            'severity_notes'       => $validated['severity_notes'] ?? null,
            'is_severity_locked'   => true,
        ];

        if (array_key_exists('recommendation', $validated) && $validated['recommendation'] !== null) {
            $updateData['recommendation'] = $validated['recommendation'];
        }

        $finding->update($updateData);

        WhatsAppService::notifySeverityReviewed($finding, $oldSeverity);

        return back()->with('success', 'Severity & Rekomendasi berhasil disimpan. Notifikasi WhatsApp telah dikirim ke Auditor.');
    }
}
