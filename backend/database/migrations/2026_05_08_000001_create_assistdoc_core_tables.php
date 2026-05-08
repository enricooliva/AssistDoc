<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('role', 32)->default('viewer');
            $table->string('auth_provider', 16)->default('local');
            $table->string('status', 32)->default('active');
        });

        Schema::create('role_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32);
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('media_type', 128);
            $table->string('storage_path');
            $table->unsignedBigInteger('size_bytes');
            $table->string('status', 32);
            $table->text('failure_reason')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
        });

        Schema::create('document_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('segment_index');
            $table->longText('content_text');
            $table->string('source_label', 120);
            $table->boolean('searchable')->default(true);
        });

        Schema::create('chat_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('status', 32)->default('active');
            $table->timestamp('last_message_at')->nullable();
        });

        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('actor_type', 16);
            $table->longText('body');
            $table->string('response_state', 64)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('message_citations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_segment_id')->constrained('document_segments')->cascadeOnDelete();
            $table->text('quote_text');
            $table->string('source_label', 120);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('query_text');
            $table->unsignedInteger('result_count');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('target_type', 120)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('outcome', 16);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('message_citations');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('document_segments');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('role_assignments');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['tenant_id', 'role', 'auth_provider', 'status']);
        });

        Schema::dropIfExists('tenants');
    }
};
