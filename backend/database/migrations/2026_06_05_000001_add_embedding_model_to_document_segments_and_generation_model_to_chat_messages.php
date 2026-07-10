<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_segments', function (Blueprint $table): void {
            $table->string('embedding_model', 120)->nullable()->after('retrieval_model_profile_id');
        });

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->string('generation_model', 120)->nullable()->after('response_state');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropColumn('generation_model');
        });

        Schema::table('document_segments', function (Blueprint $table): void {
            $table->dropColumn('embedding_model');
        });
    }
};
