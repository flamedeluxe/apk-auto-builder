<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('package_name')->unique();
            $table->string('application_name');
            $table->text('description')->nullable();
            $table->string('gitverse_repo_url');
            $table->string('codemagic_app_id')->nullable();
            $table->string('google_play_track')->default('beta');
            $table->string('gradle_task')->default('bundleRelease');
            $table->string('build_type')->default('release');
            $table->json('email_recipients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('telegram_chat_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
