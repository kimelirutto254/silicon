<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('bio');
            $table->string('platform_link');
            $table->json('geographies');
            $table->json('topics');
            $table->json('formats');
            $table->string('status')->default('pending')->index();
            $table->string('provenance')->default('Direct submission');
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('company')->nullable();
            $table->json('search_vector')->nullable();
            $table->float('trust_score')->default(0);
            $table->string('confidence_level')->default('Low');
            $table->text('credibility_summary')->nullable();
            $table->timestamp('summary_generated_at')->nullable();
            $table->unsignedTinyInteger('data_quality_score')->default(0);
            $table->json('data_quality_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('rationale');
            $table->string('role_badge');
            $table->string('company')->nullable();
            $table->boolean('verified_identity')->default(false);
            $table->float('weight')->default(1);
            $table->float('decay_factor')->default(1);
            $table->boolean('conflict_flagged')->default(false);
            $table->boolean('conflict_confirmed')->default(false);
            $table->boolean('conflict_overridden')->default(false);
            $table->json('conflict_reasons')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'user_id']);
        });

        Schema::create('duplicate_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('possible_duplicate_id')->constrained('profiles')->cascadeOnDelete();
            $table->float('confidence')->default(0);
            $table->json('reasons')->nullable();
            $table->string('status')->default('open');
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });

        Schema::create('ecosystem_pulses', function (Blueprint $table) {
            $table->id();
            $table->date('week_starts_at')->unique();
            $table->string('headline');
            $table->text('summary');
            $table->json('metrics')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecosystem_pulses');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('duplicate_flags');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('profiles');
    }
};
