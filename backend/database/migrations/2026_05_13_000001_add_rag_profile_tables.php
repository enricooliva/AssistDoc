<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retrieval_model_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 120)->unique();
            $table->string('generation_model', 120);
            $table->string('embedding_model', 120);
            $table->string('tokenizer_key', 120);
            $table->unsignedInteger('token_window');
            $table->unsignedInteger('embedding_dimensions');
            $table->boolean('available_for_new_runs')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chunking_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 120)->unique();
            $table->unsignedInteger('chunk_size_tokens');
            $table->unsignedInteger('overlap_tokens')->default(0);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('chunk_preparation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('retrieval_model_profile_id')->constrained('retrieval_model_profiles')->restrictOnDelete();
            $table->foreignId('chunking_profile_id')->constrained('chunking_profiles')->restrictOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('queued');
            $table->string('failure_code', 120)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('preparation_validation_failures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chunk_preparation_run_id')->constrained('chunk_preparation_runs')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('failure_code', 120);
            $table->text('failure_message');
            $table->unsignedInteger('segment_index')->nullable();
            $table->unsignedInteger('measured_token_count')->nullable();
            $table->unsignedInteger('allowed_token_count')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('active_retrieval_model_profile_id')->nullable()->after('uploaded_by_user_id')
                ->constrained('retrieval_model_profiles')->nullOnDelete();
            $table->foreignId('active_chunking_profile_id')->nullable()->after('active_retrieval_model_profile_id')
                ->constrained('chunking_profiles')->nullOnDelete();
            $table->foreignId('active_preparation_run_id')->nullable()->after('active_chunking_profile_id')
                ->constrained('chunk_preparation_runs')->nullOnDelete();
        });

        Schema::table('document_segments', function (Blueprint $table): void {
            $table->foreignId('chunk_preparation_run_id')->nullable()->after('document_id')
                ->constrained('chunk_preparation_runs')->nullOnDelete();
            $table->foreignId('retrieval_model_profile_id')->nullable()->after('chunk_preparation_run_id')
                ->constrained('retrieval_model_profiles')->nullOnDelete();
            $table->foreignId('chunking_profile_id')->nullable()->after('retrieval_model_profile_id')
                ->constrained('chunking_profiles')->nullOnDelete();
            $table->unsignedInteger('token_count')->nullable()->after('content_text');
            $table->timestamp('activated_at')->nullable()->after('searchable');
            $table->timestamp('retired_at')->nullable()->after('activated_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_segments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('chunk_preparation_run_id');
            $table->dropConstrainedForeignId('retrieval_model_profile_id');
            $table->dropConstrainedForeignId('chunking_profile_id');
            $table->dropColumn(['token_count', 'activated_at', 'retired_at']);
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('active_retrieval_model_profile_id');
            $table->dropConstrainedForeignId('active_chunking_profile_id');
            $table->dropConstrainedForeignId('active_preparation_run_id');
        });

        Schema::dropIfExists('preparation_validation_failures');
        Schema::dropIfExists('chunk_preparation_runs');
        Schema::dropIfExists('chunking_profiles');
        Schema::dropIfExists('retrieval_model_profiles');
    }
};
