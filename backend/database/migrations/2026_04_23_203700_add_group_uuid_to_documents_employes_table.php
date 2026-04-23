<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents_employes', function (Blueprint $table) {
            $table->uuid('group_uuid')->nullable()->after('employe_id');
            $table->index('group_uuid', 'idx_docs_group_uuid');
        });

        $groupMap = [];

        DB::table('documents_employes')
            ->select(['id', 'employe_id', 'type_document', 'date_expiration', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($documents) use (&$groupMap) {
                foreach ($documents as $document) {
                    $createdAt = $document->created_at
                        ? \Illuminate\Support\Carbon::parse($document->created_at)->format('Y-m-d H:i:s')
                        : '';

                    $key = implode('::', [
                        $document->employe_id,
                        $document->type_document ?? '',
                        $document->date_expiration ?? '',
                        $createdAt,
                    ]);

                    if (!isset($groupMap[$key])) {
                        $groupMap[$key] = (string) Str::uuid();
                    }

                    DB::table('documents_employes')
                        ->where('id', $document->id)
                        ->update(['group_uuid' => $groupMap[$key]]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('documents_employes', function (Blueprint $table) {
            $table->dropIndex('idx_docs_group_uuid');
            $table->dropColumn('group_uuid');
        });
    }
};
