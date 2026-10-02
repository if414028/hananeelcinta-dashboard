<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prayer_requests', function (Blueprint $table): void {
            $table->string('request_type')->nullable()->after('prayer_category');
            $table->string('legacy_handler_name')->nullable()->after('handled_by');
        });

        DB::table('prayer_requests')->whereNotNull('legacy_firebase_key')->whereNotNull('admin_notes')
            ->orderBy('id')->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $details = [];
                    foreach (explode("\n", (string) $row->admin_notes) as $line) {
                        foreach ([
                            'Jenis permohonan Firebase: ' => 'request_type',
                            'Nama handler lama: ' => 'legacy_handler_name',
                            'Hasil/catatan penanganan lama: ' => 'prayer_result',
                        ] as $prefix => $column) {
                            if (str_starts_with($line, $prefix)) {
                                $value = trim(substr($line, strlen($prefix)));
                                if ($value !== '' && ($column !== 'prayer_result' || $row->prayer_result === null)) {
                                    $details[$column] = $value;
                                }
                            }
                        }
                    }
                    if ($details !== []) {
                        DB::table('prayer_requests')->where('id', $row->id)->update($details);
                    }
                }
            });

        DB::table('prayer_requests')->whereNull('handled_by')->whereIn('status', ['in_prayer', 'closed'])
            ->update(['status' => 'open']);
    }

    public function down(): void
    {
        Schema::table('prayer_requests', function (Blueprint $table): void {
            $table->dropColumn(['request_type', 'legacy_handler_name']);
        });
    }
};
