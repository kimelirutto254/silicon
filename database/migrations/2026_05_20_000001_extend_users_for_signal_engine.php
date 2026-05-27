<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linkedin_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('linkedin_id');
            $table->string('professional_role')->nullable()->after('password');
            $table->string('company')->nullable()->after('professional_role');
            $table->boolean('is_admin')->default(false)->after('company');
            $table->boolean('identity_verified')->default(false)->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'linkedin_id',
                'avatar_url',
                'professional_role',
                'company',
                'is_admin',
                'identity_verified',
            ]);
        });
    }
};
