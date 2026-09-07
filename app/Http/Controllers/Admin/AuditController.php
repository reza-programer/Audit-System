<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\AuditNotification;
use App\Models\Finding;
use App\Models\Sop;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Audit::with(['store', 'auditor', 'category'])
            ->withCount('findings')
            ->orderBy('audit_date', 'desc');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('audit_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('store', fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $audits = $query->get()
            ->map(fn ($audit) => [
                'id'             => $audit->id,
                'audit_number'   => $audit->audit_number,
                'title'          => $audit->title,
                'category'       => $audit->category?->name ?? '—',
                'category_id'    => $audit->category_id,
                'store'          => $audit->store?->name ?? '—',
                'store_code'     => $audit->store?->code ?? '—',
                'auditor'        => $audit->auditor?->name ?? '—',
                'audit_date'     => $audit->audit_date->format('d M Y'),
                'audit_time'     => $audit->audit_time ?: '09:00',
                'status'         => $audit->status,
                'findings_count' => $audit->findings_count,
            ]);

        return Inertia::render('Admin/Audits/Index', [
            'audits'     => $audits,
            'categories' => AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'filters'    => [
                'category_id' => $request->query('category_id', ''),
                'search'      => $request->query('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $upcomingAudits = Audit::with(['store:id,name,code', 'category:id,name', 'auditor:id,name', 'auditors:id,name'])
            ->whereIn('status', ['PLANNED', 'IN_PROGRESS'])
            ->orderBy('audit_date', 'asc')
            ->orderBy('audit_time', 'asc')
            ->limit(6)
            ->get()
            ->map(fn ($a) => [
                'id'                   => $a->id,
                'audit_number'         => $a->audit_number,
                'title'                => $a->title,
                'store_id'             => $a->store_id,
                'store_name'           => $a->store?->name ?? '—',
                'store_code'           => $a->store?->code ?? '',
                'category'             => $a->category?->name ?? '—',
                'audit_date'           => $a->audit_date?->format('Y-m-d'),
                'audit_date_formatted' => $a->audit_date?->format('d M Y'),
                'audit_time'           => $a->audit_time ?: '09:00',
                'status'               => $a->status,
                'lead_auditor'         => $a->auditor?->name ?? '—',
                'auditors_count'       => $a->auditors->count(),
            ]);

        return Inertia::render('Admin/Audits/Create', [
            'stores'          => Store::active()->orderBy('name')->get(['id', 'name', 'code', 'business_entity', 'type', 'address', 'area']),
            'categories'      => AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'auditors'        => User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'suggested_number' => Audit::generateNumber(),
            'upcoming_audits'  => $upcomingAudits,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number'       => 'required|string|max:50|unique:audits,audit_number',
            'title'              => 'nullable|string|max:255',
            'category_id'        => 'nullable|exists:audit_categories,id',
            'store_id'           => 'nullable|exists:stores,id',
            'custom_store_name'  => 'nullable|string|max:255',
            'auditor_ids'        => 'required|array|min:1|max:5',
            'auditor_ids.*'      => 'exists:users,id',
            'audit_date'         => 'required|date',
            'audit_time'         => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'status'             => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'              => 'nullable|string',
        ]);

        if (!empty($validated['custom_store_name'])) {
            $storeName = trim($validated['custom_store_name']);
            $cleanCodeSlug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 4)) ?: 'STR';
            $store = Store::firstOrCreate(
                ['name' => $storeName],
                [
                    'code'            => 'CSA-' . $cleanCodeSlug . '-' . rand(100, 999),
                    'business_entity' => 'CSA Retail / Unit',
                    'type'            => 'toko',
                    'status'          => 'active',
                ]
            );
            $storeId = $store->id;
        } elseif (!empty($validated['store_id'])) {
            $storeId = $validated['store_id'];
        } else {
            return back()->withErrors(['store_id' => 'Silakan pilih toko yang ada atau ketik nama toko / badan usaha baru.'])->withInput();
        }

        $leadAuditorId = $validated['auditor_ids'][0];

        $audit = Audit::create([
            'audit_number' => $validated['audit_number'],
            'title'        => $validated['title'] ?? null,
            'category_id'  => $validated['category_id'] ?? null,
            'store_id'     => $storeId,
            'auditor_id'   => $leadAuditorId,
            'audit_date'   => $validated['audit_date'],
            'audit_time'   => $validated['audit_time'] ?? '09:00',
            'location'     => $validated['location'] ?? null,
            'status'       => $validated['status'],
            'notes'        => $validated['notes'] ?? null,
        ]);

        $audit->auditors()->sync($validated['auditor_ids']);

        // Auto calculate notification schedules based on rules (H-7, H-3, H-1, Hari H)
        $audit->syncNotificationSchedules();

        WhatsAppService::notifyAuditScheduled($audit);

        return redirect()->route('admin.audits.show', $audit)->with('success', 'Data saved! Audit berhasil dibuat dengan ' . count($validated['auditor_ids']) . ' auditor.');
    }

    public function show(Audit $audit): Response
    {
        $audit->load([
            'category',
            'store',
            'auditor',
            'auditors',
            'documents.uploader',
            'notifications.rule',
            'findings.category',
            'findings.sop',
            'findings.severityReviewer',
            'findings.actionPlan',
        ]);

        // If no notifications scheduled yet, sync now
        if ($audit->notifications->isEmpty() && $audit->status === 'PLANNED') {
            $audit->syncNotificationSchedules();
            $audit->load('notifications.rule');
        }

        return Inertia::render('Admin/Audits/Show', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'category'     => $audit->category?->name,
                'category_id'  => $audit->category_id,
                'store'        => $audit->store->only(['name', 'code', 'business_entity', 'type', 'area', 'regional']),
                'auditor'      => $audit->auditor->only(['name', 'email', 'phone']),
                'auditors'     => $audit->auditors->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
                'audit_date'   => $audit->audit_date->format('d M Y'),
                'audit_time'   => $audit->audit_time ?: '09:00',
                'location'     => $audit->location ?: ($audit->store ? "{$audit->store->name} ({$audit->store->code})" : '-'),
                'status'       => $audit->status,
                'notes'        => $audit->notes,
                'documents'    => $audit->documents->map(fn ($d) => [
                    'id'            => $d->id,
                    'document_type' => $d->document_type,
                    'title'         => $d->title,
                    'file_name'     => $d->file_name,
                    'file_size'     => $d->file_size,
                    'file_url'      => $d->file_url,
                    'notes'         => $d->notes,
                    'uploaded_by'   => $d->uploader->name,
                    'created_at'    => $d->created_at->format('d M Y H:i'),
                ]),
                'notifications' => $audit->notifications->sortBy('scheduled_at')->values()->map(fn ($n) => [
                    'id'           => $n->id,
                    'rule_name'    => $n->rule->name,
                    'days_before'  => $n->rule->days_before,
                    'scheduled_at' => $n->scheduled_at->format('d M Y H:i'),
                    'sent_at'      => $n->sent_at?->format('d M Y H:i'),
                    'channel'      => $n->channel,
                    'recipient'    => $n->recipient,
                    'status'       => $n->status,
                    'error_message'=> $n->error_message,
                ]),
                'findings'     => $audit->findings->map(fn ($f) => [
                    'id'             => $f->id,
                    'category'       => $f->category->name,
                    'sop'            => $f->sop?->only(['code', 'title']),
                    'finding'        => $f->finding,
                    'loss_amount'    => $f->loss_amount,
                    'severity'       => $f->severity,
                    'status'         => $f->status,
                    'recommendation' => $f->recommendation,
                    'action_plan'    => $f->actionPlan,
                ]),
            ],
            'categories' => AuditCategory::orderBy('name')->get(['id', 'name']),
            'sops'       => Sop::active()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function edit(Audit $audit): Response
    {
        $audit->load('auditors');
        $selectedAuditorIds = $audit->auditors->pluck('id')->toArray();
        if (empty($selectedAuditorIds) && $audit->auditor_id) {
            $selectedAuditorIds = [$audit->auditor_id];
        }

        return Inertia::render('Admin/Audits/Edit', [
            'audit' => [
                'id'           => $audit->id,
                'audit_number' => $audit->audit_number,
                'title'        => $audit->title,
                'category_id'  => $audit->category_id,
                'store_id'     => $audit->store_id,
                'auditor_id'   => $audit->auditor_id,
                'auditor_ids'  => $selectedAuditorIds,
                'audit_date'   => $audit->audit_date->format('Y-m-d'),
                'audit_time'   => $audit->audit_time ?: '09:00',
                'location'     => $audit->location,
                'status'       => $audit->status,
                'notes'        => $audit->notes,
            ],
            'categories' => AuditCategory::active()->orderBy('name')->get(['id', 'name']),
            'stores'     => Store::active()->orderBy('name')->get(['id', 'name', 'code', 'business_entity', 'type', 'address', 'area']),
            'auditors'   => User::whereHas('roles', fn ($q) => $q->where('name', 'auditor'))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'audit_number'       => 'required|string|max:50|unique:audits,audit_number,' . $audit->id,
            'title'              => 'nullable|string|max:255',
            'category_id'        => 'nullable|exists:audit_categories,id',
            'store_id'           => 'nullable|exists:stores,id',
            'custom_store_name'  => 'nullable|string|max:255',
            'auditor_ids'        => 'required|array|min:1|max:5',
            'auditor_ids.*'      => 'exists:users,id',
            'audit_date'         => 'required|date',
            'audit_time'         => 'nullable|string',
            'location'           => 'nullable|string|max:255',
            'status'             => 'required|in:PLANNED,IN_PROGRESS,COMPLETED,CLOSED',
            'notes'              => 'nullable|string',
        ]);

        if (!empty($validated['custom_store_name'])) {
            $storeName = trim($validated['custom_store_name']);
            $cleanCodeSlug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 4)) ?: 'STR';
            $store = Store::firstOrCreate(
                ['name' => $storeName],
                [
                    'code'            => 'CSA-' . $cleanCodeSlug . '-' . rand(100, 999),
                    'business_entity' => 'CSA Retail / Unit',
                    'type'            => 'toko',
                    'status'          => 'active',
                ]
            );
            $storeId = $store->id;
        } elseif (!empty($validated['store_id'])) {
            $storeId = $validated['store_id'];
        } else {
            $storeId = $audit->store_id;
        }

        $leadAuditorId = $validated['auditor_ids'][0];

        $audit->update([
            'audit_number' => $validated['audit_number'],
            'title'        => $validated['title'] ?? null,
            'category_id'  => $validated['category_id'] ?? null,
            'store_id'     => $storeId,
            'auditor_id'   => $leadAuditorId,
            'audit_date'   => $validated['audit_date'],
            'audit_time'   => $validated['audit_time'] ?? '09:00',
            'location'     => $validated['location'] ?? null,
            'status'       => $validated['status'],
            'notes'        => $validated['notes'] ?? null,
        ]);

        $audit->auditors()->sync($validated['auditor_ids']);

        // Resync schedules with updated audit_date
        $audit->syncNotificationSchedules();

        return redirect()->route('admin.audits.show', $audit)->with('success', 'Data saved! Audit berhasil diperbarui & jadwal notifikasi disinkronkan.');
    }

    public function destroy(Audit $audit): RedirectResponse
    {
        $audit->delete();

        return redirect()->route('admin.audits.index')->with('success', 'Audit berhasil dihapus.');
    }

    public function sendNotificationNow(AuditNotification $notification): RedirectResponse
    {
        $ok = AuditNotificationService::dispatch($notification);

        return back()->with($ok ? 'success' : 'error', $ok ? 'Notifikasi WhatsApp berhasil dikirim.' : ($notification->error_message ?: 'Gagal mengirim notifikasi.'));
    }

    public function storeFinding(Request $request, Audit $audit): RedirectResponse
    {
        $validated = $request->validate([
            'category_id'     => 'required|exists:audit_categories,id',
            'sop_id'          => 'nullable|exists:sops,id',
            'finding'         => 'required|string',
            'loss_amount'     => 'nullable|numeric|min:0',
            'auditor_opinion' => 'nullable|string',
            'recommendation'  => 'required|string',
            'severity'        => 'required|in:CRITICAL,MAJOR,MINOR,OBSERVATION',
            'status'          => 'required|in:OPEN,IN_PROGRESS,WAITING_VERIFICATION,VERIFIED,CLOSED',
        ]);

        $finding = $audit->findings()->create($validated);
        $finding->actionPlan()->create(['status' => 'OPEN']);
        $finding->followUp()->create([]);

        WhatsAppService::notifyFindingCreated($finding);

        return back()->with('success', 'Finding berhasil ditambahkan & notifikasi WhatsApp terkirim.');
    }
}
