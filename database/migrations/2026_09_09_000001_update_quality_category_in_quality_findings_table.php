<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_findings', function (Blueprint $table) {
            $table->string('quality_category', 255)->change();
        });

        // Convert existing plain string categories to JSON array format
        $records = DB::table('quality_findings')->get();
        foreach ($records as $row) {
            if (!empty($row->quality_category) && !str_starts_with(trim($row->quality_category), '[')) {
                DB::table('quality_findings')
                    ->where('id', $row->id)
                    ->update([
                        'quality_category' => json_encode([$row->quality_category]),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quality_findings', function (Blueprint $table) {
            $table->string('quality_category', 50)->change();
        });
    }
};
