<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->timestamp('deleted_at')->nullable()->after('indexed_at');
            $table->foreignId('deleted_by_user_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deleted_by_user_id');
            $table->dropColumn('deleted_at');
        });
    }
};
