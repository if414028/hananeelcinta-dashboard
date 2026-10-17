<?php

use App\Support\CongregationProfileFields;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->string('legacy_username')->nullable();
            $table->string('legacy_role')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
        });

        DB::table('congregations')->whereNotNull('notes')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $fields = CongregationProfileFields::extract($row->notes, (array) $row);
                // Retain the original notes as a migration backup; forms display only the remaining notes.
                unset($fields['notes']);
                foreach (['children_names', 'siblings_names'] as $column) {
                    if (isset($fields[$column])) {
                        $fields[$column] = json_encode($fields[$column], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    }
                }
                if ($fields !== []) {
                    DB::table('congregations')->where('id', $row->id)->update($fields);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('congregations')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $notes = $row->notes ?? '';
                foreach (['legacy_username', 'legacy_role', 'father_name', 'mother_name'] as $column) {
                    if ($row->{$column} !== null && ! str_contains($notes, CongregationProfileFields::LABELS[$column].': '.$row->{$column})) {
                        $notes = trim($notes."\n".CongregationProfileFields::LABELS[$column].': '.$row->{$column});
                    }
                }
                DB::table('congregations')->where('id', $row->id)->update(['notes' => $notes ?: null]);
            }
        });
        Schema::table('congregations', function (Blueprint $table): void {
            $table->dropColumn(['legacy_username', 'legacy_role', 'father_name', 'mother_name']);
        });
    }
};
