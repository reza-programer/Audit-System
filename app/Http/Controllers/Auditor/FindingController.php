<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\Finding;
use App\Models\FindingFollowUp;
use App\Models\Sop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FindingController extends Controller
{
    public function create(Request $request, Audit $audit): Response
    {
        $this->authorize('view', $audit);

        return Inertia::render('Auditor/Findings/Create', [
            'audit'      => $audit->load(['store', 'category'])->only(['id', 'audit_number', 'store', 'category', 'category_id']),
            'categories' => AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'sops'       => Sop::active()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);
        $this->authorize('create', Finding::class);

        $validated = $request->validate([
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
            'severity'        => 'required|in:MINOR,MEDIUM,MAJOR,CRITICAL,OBSERVATION',
        ]);

        $finding = $audit->findings()->create([
            ...$validated,
            'status' => Finding::STATUS_OPEN,
        ]);

        // Create empty ActionPlan record
        $finding->actionPlan()->create([
            'status' => 'OPEN',
        ]);

        // Create empty FindingFollowUp record
        $finding->followUp()->create([]);

        \App\Services\WhatsAppService::notifyFindingCreated($finding);

        return redirect()->route('auditor.audits.show', $audit)
            ->with('success', 'Data saved! Temuan (Finding) baru berhasil disimpan.');
    }

    public function destroy(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('delete', $finding);

        $audit = $finding->audit;
        $finding->delete();

        return redirect()->route('auditor.audits.show', $audit)
            ->with('success', 'Temuan (Finding) berhasil dihapus.');
    }

    public function show(Request $request, Finding $finding): Response
    {
        $this->authorize('view', $finding);

        $finding->load([
            'audit.store',
            'audit.documents',
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

        return Inertia::render('Auditor/Findings/Show', [
            'finding' => [
                'id'                   => $finding->id,
                'audit'                => [
                    'id'              => $finding->audit->id,
                    'audit_number'    => $finding->audit->audit_number,
                    'status'          => $finding->audit->status,
                    'documents_count' => $docsCount,
                    'has_documents'   => $docsCount > 0,
                ],
                'has_documents'        => $hasDocs,
                'documents_count'      => $docsCount,
                'has_action_plan'      => $hasActionPlan,
                'store'                => $finding->audit->store->only(['name', 'code']),
                'category'             => $finding->category->name,
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
                    'can_verify'          => $e->isPending(),
                ]),
                'can_close' => $finding->isCloseable(),
            ],
        ]);
    }

    public function edit(Request $request, Finding $finding): Response
    {
        $this->authorize('update', $finding);

        return Inertia::render('Auditor/Findings/Edit', [
            'finding'    => $finding->load(['audit.store', 'category', 'sop']),
            'categories' => AuditCategory::active()->get(['id', 'name']),
            'sops'       => Sop::active()->get(['id', 'code', 'title']),
        ]);
    }

    public function update(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        $rules = [
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
        ];

        // Only validate severity if it has not been locked by Coordinator
        if (!$finding->is_severity_locked) {
            $rules['severity'] = 'required|in:MINOR,MEDIUM,MAJOR,CRITICAL,OBSERVATION';
        }

        $validated = $request->validate($rules);

        // Prevent modifying severity if locked
        if ($finding->is_severity_locked) {
            unset($validated['severity']);
        }

        $finding->update($validated);

        return redirect()->route('auditor.findings.show', $finding)
            ->with('success', 'Finding berhasil diperbarui.');
    }

    public function close(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('close', $finding);

        $finding->update(['status' => Finding::STATUS_CLOSED]);

        // Update action plan status
        $finding->actionPlan?->update(['status' => 'COMPLETED']);

        \App\Services\WhatsAppService::notifyFindingClosed($finding);

        return redirect()->route('auditor.findings.show', $finding)
            ->with('success', 'Data saved! Finding berhasil ditutup.');
    }

    public function updateActionPlan(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        $validated = $request->validate([
            'action_plan' => 'required|string',
            'pic'         => 'nullable|string|max:100',
            'deadline'    => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        $finding->actionPlan()->updateOrCreate(
            ['finding_id' => $finding->id],
            [
                'action_plan' => $validated['action_plan'],
                'pic'         => $validated['pic'] ?? null,
                'deadline'    => $validated['deadline'] ?? null,
                'response'    => $validated['notes'] ?? null,
                'status'      => 'IN_PROGRESS',
            ]
        );

        if ($finding->status === Finding::STATUS_OPEN) {
            $finding->update(['status' => Finding::STATUS_IN_PROGRESS]);
        }

        return redirect()->route('auditor.findings.show', $finding)
            ->with('success', 'Data saved! Komitmen tindak lanjut toko berhasil disimpan.');
    }

    public function storeEvidence(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'file'        => 'required|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        $disk = config('filesystems.default');
        $path = $request->file('file')->store('evidences', $disk);

        $finding->evidences()->create([
            'uploaded_by'         => $request->user()->id,
            'file'                => $path,
            'description'         => $validated['description'],
            'verification_status' => 'APPROVED',
            'verified_by'         => $request->user()->id,
            'verified_at'         => now(),
        ]);

        return redirect()->route('auditor.findings.show', $finding)
            ->with('success', 'Data saved! Bukti tindak lanjut berhasil diunggah.');
    }
}
