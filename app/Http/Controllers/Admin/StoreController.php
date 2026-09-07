<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Store::withCount('audits')->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('business_entity', 'like', "%{$search}%")
                  ->orWhere('area', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $stores = $query->get()->map(fn ($s) => [
            'id'              => $s->id,
            'code'            => $s->code,
            'name'            => $s->name,
            'business_entity' => $s->business_entity ?: '-',
            'type'            => $s->type ?: 'toko',
            'area'            => $s->area ?: '-',
            'regional'        => $s->regional ?: '-',
            'address'         => $s->address ?: '',
            'status'          => $s->status,
            'audits_count'    => $s->audits_count,
        ]);

        return Inertia::render('Admin/Stores/Index', [
            'stores'  => $stores,
            'filters' => [
                'search' => $request->query('search', ''),
                'type'   => $request->query('type', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Stores/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:20|unique:stores,code',
            'name'            => 'required|string|max:255',
            'business_entity' => 'nullable|string|max:100',
            'type'            => 'required|in:toko,gudang,head_office,hub',
            'area'            => 'nullable|string|max:100',
            'regional'        => 'nullable|string|max:100',
            'address'         => 'nullable|string',
            'status'          => 'required|in:active,inactive',
        ]);

        Store::create($validated);

        return redirect()->route('admin.stores.index')->with('success', 'Data saved! Toko / Gudang berhasil ditambahkan.');
    }

    public function edit(Store $store): Response
    {
        return Inertia::render('Admin/Stores/Edit', [
            'store' => [
                'id'              => $store->id,
                'code'            => $store->code,
                'name'            => $store->name,
                'business_entity' => $store->business_entity,
                'type'            => $store->type ?: 'toko',
                'area'            => $store->area,
                'regional'        => $store->regional,
                'address'         => $store->address,
                'status'          => $store->status,
            ],
        ]);
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:20|unique:stores,code,' . $store->id,
            'name'            => 'required|string|max:255',
            'business_entity' => 'nullable|string|max:100',
            'type'            => 'required|in:toko,gudang,head_office,hub',
            'area'            => 'nullable|string|max:100',
            'regional'        => 'nullable|string|max:100',
            'address'         => 'nullable|string',
            'status'          => 'required|in:active,inactive',
        ]);

        $store->update($validated);

        return redirect()->route('admin.stores.index')->with('success', 'Data saved! Toko / Gudang berhasil diperbarui.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        if ($store->audits()->exists()) {
            return back()->with('error', 'Toko tidak dapat dihapus karena memiliki riwayat audit.');
        }

        $store->delete();

        return redirect()->route('admin.stores.index')->with('success', 'Toko berhasil dihapus.');
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Gagal membaca file CSV.');
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong.');
        }

        $header = array_map(fn ($h) => strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h))), $header);

        $inserted = 0;
        $updated = 0;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 2) continue;

            $data = array_combine(array_slice($header, 0, count($row)), array_slice($row, 0, count($header)));

            $code           = trim($data['code'] ?? $data['kode'] ?? $data['kode_toko'] ?? '');
            $name           = trim($data['name'] ?? $data['nama'] ?? $data['nama_toko'] ?? '');
            $businessEntity = trim($data['business_entity'] ?? $data['badan_usaha'] ?? $data['pt'] ?? '');
            $type           = strtolower(trim($data['type'] ?? $data['tipe'] ?? 'toko'));
            $area           = trim($data['area'] ?? $data['wilayah'] ?? '');
            $regional       = trim($data['regional'] ?? $data['region'] ?? '');
            $address        = trim($data['address'] ?? $data['alamat'] ?? $data['alamat_lengkap'] ?? '');
            $status         = strtolower(trim($data['status'] ?? 'active'));

            if (empty($code) || empty($name)) continue;

            if (!in_array($type, ['toko', 'gudang', 'head_office', 'hub'])) {
                $type = 'toko';
            }
            if (!in_array($status, ['active', 'inactive'])) {
                $status = 'active';
            }

            $store = Store::updateOrCreate(
                ['code' => $code],
                [
                    'name'            => $name,
                    'business_entity' => $businessEntity ?: null,
                    'type'            => $type,
                    'area'            => $area ?: null,
                    'regional'        => $regional ?: null,
                    'address'         => $address ?: null,
                    'status'          => $status,
                ]
            );

            if ($store->wasRecentlyCreated) {
                $inserted++;
            } else {
                $updated++;
            }
        }

        fclose($handle);

        return redirect()->route('admin.stores.index')
            ->with('success', "Data saved! Import CSA berhasil. {$inserted} data baru, {$updated} data diperbarui.");
    }

    /**
     * Download / Export complete store data in styled Excel (.xls) format
     */
    public function downloadTemplate(): StreamedResponse
    {
        $filename = 'Master_Data_Toko_CSA_' . date('Y-m-d_His') . '.xls';

        $headers = [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () {
            $stores = Store::withCount('audits')->orderBy('code')->get();
            $totalStores = $stores->count();
            $totalActive = $stores->where('status', 'active')->count();
            $totalInactive = $stores->where('status', 'inactive')->count();
            $totalAudits = $stores->sum('audits_count');

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
            echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Master Toko CSA</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
            echo '<style>';
            echo 'body { font-family: "Segoe UI", Calibri, Arial, sans-serif; font-size: 10pt; color: #1e293b; background-color: #ffffff; }';
            echo '.header-banner { background-color: #0f172a; color: #ffffff; font-size: 15pt; font-weight: bold; text-align: center; vertical-align: middle; height: 42px; border: 1px solid #0f172a; }';
            echo '.sub-banner { background-color: #1e293b; color: #94a3b8; font-size: 9pt; text-align: center; vertical-align: middle; height: 26px; border: 1px solid #1e293b; }';
            echo '.kpi-title { background-color: #f1f5f9; color: #475569; font-size: 8.5pt; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; height: 20px; }';
            echo '.kpi-val { background-color: #ffffff; color: #0f172a; font-size: 12pt; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; height: 28px; }';
            echo '.kpi-val-green { background-color: #f0fdf4; color: #166534; font-size: 12pt; font-weight: bold; text-align: center; border: 1px solid #bbf7d0; height: 28px; }';
            echo '.kpi-val-slate { background-color: #f8fafc; color: #64748b; font-size: 12pt; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; height: 28px; }';
            echo '.kpi-val-blue { background-color: #eff6ff; color: #1d4ed8; font-size: 12pt; font-weight: bold; text-align: center; border: 1px solid #bfdbfe; height: 28px; }';
            echo 'th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #1e3a8a; padding: 10px 8px; font-size: 10pt; vertical-align: middle; height: 32px; }';
            echo 'td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 9.5pt; vertical-align: middle; }';
            echo '.code-cell { mso-number-format:"\@"; font-family: Consolas, "Courier New", monospace; font-weight: bold; text-align: center; background-color: #f1f5f9; color: #0f172a; }';
            echo '.text-cell { mso-number-format:"\@"; }';
            echo '.center { text-align: center; }';
            echo '.right { text-align: right; }';
            echo '.zebra { background-color: #f8fafc; }';
            echo '.badge-active { background-color: #dcfce7; color: #15803d; font-weight: bold; text-align: center; border: 1px solid #86efac; }';
            echo '.badge-inactive { background-color: #f1f5f9; color: #64748b; font-weight: bold; text-align: center; border: 1px solid #cbd5e1; }';
            echo '.summary-bar { background-color: #f8fafc; font-weight: bold; border-top: 2px solid #0f172a; border-bottom: 3px double #0f172a; height: 30px; }';
            echo '.footnote { font-size: 8.5pt; color: #64748b; font-style: italic; border: none; padding-top: 10px; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table border="1" cellpadding="0" cellspacing="0">';

            // Main Header Banner
            echo '<tr><td colspan="10" class="header-banner">MASTER DATA UNIT TOKO &amp; CABANG CSA</td></tr>';
            echo '<tr><td colspan="10" class="sub-banner">Sistem Audit Internal &amp; Retail Compliance • Tanggal Unduh: ' . date('d/m/Y H:i:s') . ' WIB • Generated by Internal Audit System</td></tr>';
            echo '<tr><td colspan="10" style="border:none; height: 10px;"></td></tr>';

            // KPI Summary Cards Bar
            echo '<tr>';
            echo '<td colspan="2" class="kpi-title">TOTAL TOKO TERDAFTAR</td>';
            echo '<td colspan="3" class="kpi-title">UNIT AKTIF OPERASIONAL</td>';
            echo '<td colspan="3" class="kpi-title">UNIT NONAKTIF / TUTUP</td>';
            echo '<td colspan="2" class="kpi-title">TOTAL RIWAYAT AUDIT</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td colspan="2" class="kpi-val">' . $totalStores . ' Unit</td>';
            echo '<td colspan="3" class="kpi-val-green">' . $totalActive . ' Unit Aktif</td>';
            echo '<td colspan="3" class="kpi-val-slate">' . $totalInactive . ' Unit Nonaktif</td>';
            echo '<td colspan="2" class="kpi-val-blue">' . $totalAudits . ' Audit</td>';
            echo '</tr>';
            echo '<tr><td colspan="10" style="border:none; height: 12px;"></td></tr>';

            // Table Header
            echo '<thead>';
            echo '<tr>';
            echo '<th style="width: 45px;">No</th>';
            echo '<th style="width: 110px;">Kode CSA</th>';
            echo '<th style="width: 240px;">Nama Toko / Unit</th>';
            echo '<th style="width: 420px;">Alamat Lengkap Toko</th>';
            echo '<th style="width: 130px;">Area</th>';
            echo '<th style="width: 120px;">Regional</th>';
            echo '<th style="width: 160px;">Badan Usaha</th>';
            echo '<th style="width: 95px;">Tipe</th>';
            echo '<th style="width: 105px;">Status</th>';
            echo '<th style="width: 100px;">Jml Audit</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $no = 1;
            foreach ($stores as $store) {
                $zebra = ($no % 2 === 0) ? ' class="zebra"' : '';
                $statusClass = ($store->status === 'active') ? 'badge-active' : 'badge-inactive';
                $statusLabel = ($store->status === 'active') ? 'AKTIF' : 'NONAKTIF';

                echo "<tr{$zebra}>";
                echo '<td class="center">' . $no++ . '</td>';
                echo '<td class="code-cell">' . htmlspecialchars($store->code) . '</td>';
                echo '<td class="text-cell" style="font-weight: 600; color: #0f172a;">' . htmlspecialchars($store->name) . '</td>';
                echo '<td class="text-cell" style="font-size: 9pt; color: #334155; mso-char-wrap:1;">' . htmlspecialchars($store->address ?: '—') . '</td>';
                echo '<td class="center">' . htmlspecialchars($store->area ?: '—') . '</td>';
                echo '<td class="center">' . htmlspecialchars($store->regional ?: '—') . '</td>';
                echo '<td>' . htmlspecialchars($store->business_entity ?: '—') . '</td>';
                echo '<td class="center" style="text-transform: uppercase;">' . htmlspecialchars($store->type ?: 'toko') . '</td>';
                echo '<td class="' . $statusClass . '">' . $statusLabel . '</td>';
                echo '<td class="center" style="font-weight: 600;">' . $store->audits_count . '</td>';
                echo '</tr>';
            }

            // Summary Footer
            echo '<tr class="summary-bar">';
            echo '<td colspan="3" style="text-align: right; font-weight: bold; color: #0f172a; padding-right: 12px;">TOTAL KESELURUHAN:</td>';
            echo '<td colspan="5" style="font-weight: bold; color: #0f172a;">' . $totalStores . ' Unit Toko / Cabang CSA Terdata</td>';
            echo '<td class="center badge-active">' . $totalActive . ' Aktif</td>';
            echo '<td class="center" style="font-weight: bold; color: #1d4ed8;">' . $totalAudits . '</td>';
            echo '</tr>';

            // Footnote
            echo '<tr><td colspan="10" style="border:none; height: 10px;"></td></tr>';
            echo '<tr><td colspan="10" class="footnote">* Dokumen ini diunduh langsung dari Sistem Audit Internal Retail. Seluruh data unit dan alamat telah disinkronisasi dengan master data operasional CSA terkini.</td></tr>';

            echo '</tbody>';
            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, 200, $headers);
    }
}

