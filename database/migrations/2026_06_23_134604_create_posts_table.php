<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('text_id')->constrained('texts')->cascadeOnDelete();
            $table->string('hook_propose',280)->nullable();
            $table->json('body_points')->nullable();
            $table->unsignedTinyInteger('technical_readability_score')->nullable();
            $table->json('suggested_hashtags')->nullable();
            $table->text('tone_compliance_justification')->nullable();
            $table->longText('payload_brut')->nullable();
            $table->enum('status', ['draft', 'posted', 'archived'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
