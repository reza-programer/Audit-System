<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditDocumentController extends Controller
{
    public function store(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorize('view', $audit);

        $validated = $request->validate([
            'document_type' => 'required|in:LHP,BAP,OTHER',
            'title'         => 'required|string|max:255',
            'finding_id'    => 'nullable|exists:findings,id',
            'file'          => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp|max:10240', // max 10MB
            'notes'         => 'nullable|string',
        ]);

        $file = $request->file('file');
        $disk = config('filesystems.default');
        $path = $file->store('audit-documents', $disk);

        $audit->documents()->create([
            'document_type' => $validated['document_type'],
            'title'         => $validated['title'],
            'finding_id'    => $validated['finding_id'] ?? null,
            'file_path'     => $path,
            'file_name'     => $file->getClientOriginalName(),
            'file_size'     => round($file->getSize() / 1024, 1) . ' KB',
            'notes'         => $validated['notes'] ?? null,
            'uploaded_by'   => $request->user()->id,
        ]);

        return back()->with('success', 'Data saved! Dokumen ' . $validated['document_type'] . ' bertanda tangan berhasil diunggah.');
    }

    public function destroy(AuditDocument $document): RedirectResponse
    {
        $this->authorize('view', $document->audit);

        $disk = config('filesystems.default');
        if ($document->file_path && Storage::disk($disk)->exists($document->file_path)) {
            Storage::disk($disk)->delete($document->file_path);
        } elseif ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Dokumen audit berhasil dihapus.');
    }
}
