<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('mfa_policy', 32)->default('optional')->after('status');
            $table->timestamp('locked_at')->nullable()->after('last_login_at');
            $table->timestamp('locked_until')->nullable()->after('locked_at');
            $table->string('lockout_reason', 120)->nullable()->after('locked_until');
            $table->boolean('password_reset_required')->default(false)->after('lockout_reason');
        });

        Schema::create('user_access_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('method', 32);
            $table->boolean('enabled')->default(true);
            $table->string('managed_by', 32)->default('platform');
            $table->timestamps();
            $table->unique(['user_id', 'method']);
        });

        Schema::create('password_reset_journeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('initiated');
            $table->string('reset_token', 120)->unique();
            $table->timestamp('requested_at');
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('requested_by_ip', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('mfa_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('authentication_method', 32);
            $table->string('status', 32)->default('pending');
            $table->boolean('required_by_policy')->default(true);
            $table->string('verification_code_hash');
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('initiated_at');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lockout_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trigger_type', 32);
            $table->string('status', 32)->default('active');
            $table->unsignedInteger('failure_threshold');
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('locked_at');
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lockout_records');
        Schema::dropIfExists('mfa_challenges');
        Schema::dropIfExists('password_reset_journeys');
        Schema::dropIfExists('user_access_methods');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'mfa_policy',
                'locked_at',
                'locked_until',
                'lockout_reason',
                'password_reset_required',
            ]);
        });
    }
};
