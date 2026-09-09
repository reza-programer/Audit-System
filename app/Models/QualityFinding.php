<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityFinding extends Model
{
    use SoftDeletes;

    protected $table = 'quality_findings';

    protected $fillable = [
        'finding_id',
        'audit_id',
        'quality_category',
        'title',
        'impact_amount',
        'root_cause',
        'systemic_issue',
        'recommendation',
        'auditor_notes',
        'reported_by',
        'status',
    ];

    protected $casts = [
        'impact_amount' => 'decimal:2',
    ];

    protected $appends = [
        'quality_categories',
    ];

    public function getQualityCategoriesAttribute(): array
    {
        $val = $this->attributes['quality_category'] ?? null;
        if (empty($val)) {
            return [];
        }
        if (is_array($val)) {
            return $val;
        }
        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values($decoded);
        }
        return array_values(array_filter(array_map('trim', explode(',', $val))));
    }

    public function setQualityCategoryAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['quality_category'] = json_encode(array_values($value));
        } else {
            $this->attributes['quality_category'] = $value;
        }
    }

    public function getQualityCategoriesInfoAttribute(): array
    {
        $all = self::categories();
        return array_map(fn ($k) => $all[$k] ?? [
            'id'          => $k,
            'code'        => '-',
            'label'       => $k,
            'description' => '',
        ], $this->quality_categories);
    }

    const CATEGORY_IMPACT_50M    = 'impact_50m';
    const CATEGORY_FRAUD_RISK    = 'fraud_risk';
    const CATEGORY_SYSTEM_CONTROL = 'system_control';
    const CATEGORY_ORG_STRUCTURE = 'org_structure';

    public static function categories(): array
    {
        return [
            self::CATEGORY_IMPACT_50M => [
                'id'          => self::CATEGORY_IMPACT_50M,
                'code'        => '01',
                'label'       => 'Impact ≥ Rp 50 Juta',
                'description' => 'Temuan yang berdampak pada finansial ataupun potensi kerugian yang mencapai Rp.50.000.000 atau lebih',
            ],
            self::CATEGORY_FRAUD_RISK => [
                'id'          => self::CATEGORY_FRAUD_RISK,
                'code'        => '02',
                'label'       => 'Risiko Fraud / Manipulasi',
                'description' => 'Indikasi kecurangan, penggelapan, manipulasi data sistem, atau transaksi fiktif',
            ],
            self::CATEGORY_SYSTEM_CONTROL => [
                'id'          => self::CATEGORY_SYSTEM_CONTROL,
                'code'        => '03',
                'label'       => 'Masalah Sistem / Kontrol Besar',
                'description' => 'Kelemahan SOP fundamental, celah keamanan sistem IT/POS, atau breakdown kontrol internal',
            ],
            self::CATEGORY_ORG_STRUCTURE => [
                'id'          => self::CATEGORY_ORG_STRUCTURE,
                'code'        => '04',
                'label'       => 'Struktur Organisasi Bermasalah',
                'description' => 'Rangkap jabatan kritis (conflict of interest), ketiadaan supervisi, atau staffing bermasalah',
            ],
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
